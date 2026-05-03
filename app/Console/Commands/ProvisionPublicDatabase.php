<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProvisionPublicDatabase extends Command
{
    protected $signature = 'transparencia:provision-public-db';

    protected $description = 'Aprovisiona la BD pública spp_public y el rol spp_portal de forma idempotente.';

    public function handle(): int
    {
        $publicDb = config('database.connections.pgsql_public.database');
        $portalUser = config('database.connections.pgsql_public_read.username');
        $portalPassword = config('database.connections.pgsql_public_read.password');

        if (empty($portalPassword)) {
            $this->error('DB_PUBLIC_PORTAL_PASSWORD no está configurado.');

            return self::FAILURE;
        }

        $admin = DB::connection('pgsql');

        // 1. CREATE DATABASE
        $dbExists = ! empty($admin->select('SELECT 1 FROM pg_database WHERE datname = ?', [$publicDb]));
        if ($dbExists) {
            $this->info("BD '{$publicDb}' ya existe, omitiendo CREATE DATABASE.");
        } else {
            // CREATE DATABASE no admite parámetros bindados; usar quoted identifier.
            $admin->statement('CREATE DATABASE "'.str_replace('"', '""', $publicDb).'"');
            $this->info("BD '{$publicDb}' creada.");
        }

        // 2. CREATE ROLE
        $roleExists = ! empty($admin->select('SELECT 1 FROM pg_roles WHERE rolname = ?', [$portalUser]));
        if ($roleExists) {
            $this->info("Rol '{$portalUser}' ya existe, omitiendo CREATE ROLE.");
        } else {
            $admin->statement(sprintf(
                'CREATE ROLE "%s" LOGIN PASSWORD %s NOSUPERUSER NOINHERIT NOCREATEDB NOCREATEROLE',
                str_replace('"', '""', $portalUser),
                $admin->getPdo()->quote($portalPassword),
            ));
            $this->info("Rol '{$portalUser}' creado.");
        }

        // 3. GRANT CONNECT
        $admin->statement(sprintf(
            'GRANT CONNECT ON DATABASE "%s" TO "%s"',
            str_replace('"', '""', $publicDb),
            str_replace('"', '""', $portalUser),
        ));

        // 4. GRANT USAGE en schema public dentro de spp_public
        // Necesitamos conectarnos a la BD recién creada para el GRANT del schema.
        DB::purge('pgsql_public');
        DB::connection('pgsql_public')->statement(sprintf(
            'GRANT USAGE ON SCHEMA public TO "%s"',
            str_replace('"', '""', $portalUser),
        ));

        $this->info('Aprovisionamiento completado.');

        activity('public-db-provisioning')
            ->withProperties(['database' => $publicDb, 'role' => $portalUser])
            ->log('BD pública aprovisionada');

        return self::SUCCESS;
    }
}
