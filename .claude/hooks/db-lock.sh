#!/usr/bin/env bash
# Lock de BD compartida entre sesiones paralelas de Claude Code (dte-spp).
# Bloquea comandos que tocan la BD dev/testing (artisan test / migrate / db:seed)
# cuando OTRA sesión los está corriendo. Modo: $1 = pre | post.
# Lee el JSON del hook por stdin (session_id, tool_input.command).

set -u
MODE="${1:-pre}"
LOCK="/tmp/dte-spp-db.lock"
TTL=1800  # 30 min: un lock más viejo se considera huérfano (sesión muerta)

INPUT="$(cat)"
CMD="$(printf '%s' "$INPUT" | jq -r '.tool_input.command // empty' 2>/dev/null)"
SID="$(printf '%s' "$INPUT" | jq -r '.session_id // "unknown"' 2>/dev/null)"

# Comandos que tocan la BD compartida. Excluye lecturas (migrate:status, migrate:list).
# Excluye también comandos dirigidos al repo geobase (BD/contenedor propios, sin conflicto).
matches_db_cmd() {
  printf '%s' "$CMD" | grep -q 'laravel/geobase' && return 1
  printf '%s' "$CMD" | grep -qE 'artisan +(test|db:seed|migrate:fresh|migrate:refresh|migrate:rollback|migrate([^:a-zA-Z]|$))'
}

if ! matches_db_cmd; then
  exit 0
fi

if [ "$MODE" = "post" ]; then
  # Liberar solo el lock propio
  if [ -f "$LOCK" ]; then
    OWNER="$(jq -r '.session_id // empty' "$LOCK" 2>/dev/null)"
    [ "$OWNER" = "$SID" ] && rm -f "$LOCK"
  fi
  exit 0
fi

# MODE=pre: verificar/tomar el lock
if [ -f "$LOCK" ]; then
  OWNER="$(jq -r '.session_id // empty' "$LOCK" 2>/dev/null)"
  TS="$(jq -r '.ts // 0' "$LOCK" 2>/dev/null)"
  AGE=$(( $(date +%s) - TS ))
  if [ "$OWNER" != "$SID" ] && [ "$AGE" -lt "$TTL" ]; then
    OCMD="$(jq -r '.cmd // "?"' "$LOCK" 2>/dev/null)"
    echo "🔒 BD ocupada por otra sesión Claude (hace ${AGE}s): ${OCMD}. Espera a que termine o coordina vía .claude/COORDINACION.md; si es un lock huérfano: rm $LOCK" >&2
    exit 2
  fi
fi

jq -n --arg sid "$SID" --arg cmd "$(printf '%s' "$CMD" | head -c 120)" --argjson ts "$(date +%s)" \
  '{session_id: $sid, ts: $ts, cmd: $cmd}' > "$LOCK"
exit 0
