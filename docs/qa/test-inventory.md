# Inventario de Tests — DTE-SPP 2026

**Total: 670 tests en 122 clases**  
**Fecha de generación:** 2026-03-14  
**Comando:** `./vendor/bin/sail artisan test`

---

## Resumen por Dominio

| Dominio | Clases | Tests |
|---------|-------:|------:|
| Feature Tests — General | 34 | 131 |
| Feature — Administración y Auditoría | 6 | 33 |
| Feature — Cascade / PED | 1 | 4 |
| Feature — Componentes UI | 3 | 10 |
| Feature — Dashboard | 2 | 4 |
| Feature — Embeddings / IA | 3 | 20 |
| Feature — Evaluación | 8 | 43 |
| Feature — Exportaciones / Reportes | 5 | 22 |
| Feature — MML (Planeación) | 27 | 172 |
| Feature — Notificaciones | 2 | 8 |
| Feature — Presupuesto | 1 | 23 |
| Feature — Seeders | 1 | 12 |
| Feature — Seguimiento | 11 | 65 |
| Feature — Usuarios e Invitaciones | 6 | 31 |
| Unit — Embeddings / IA | 3 | 28 |
| Unit Tests | 4 | 21 |
| Unit — LLM Service | 2 | 23 |
| Unit — MML | 1 | 6 |
| Unit — Services | 1 | 12 |
| Unit — Tracking | 1 | 2 |
| **TOTAL** | **122** | **670** |

---

## Detalle por Clase

### Feature Tests — General (131 tests)

#### `AccessibilityTest` (2 tests)
> `tests/Feature/AccessibilityTest.php`

- `test_hamburger_has_aria_label`
- `test_skip_to_main_link_is_present`

#### `AislamientoMultiURTest` (2 tests)
> `tests/Feature/AislamientoMultiURTest.php`

- `test_coordinadora_accede`
- `test_usuario_sin_relacion_es_bloqueado`

#### `ApiTokenPermissionsTest` (1 tests)
> `tests/Feature/ApiTokenPermissionsTest.php`

- `test_api_token_permissions_can_be_updated`

#### `AuthenticationTest` (3 tests)
> `tests/Feature/AuthenticationTest.php`

- `test_login_screen_can_be_rendered`
- `test_users_can_authenticate_using_the_login_screen`
- `test_users_can_not_authenticate_with_invalid_password`

#### `BrowserSessionsTest` (1 tests)
> `tests/Feature/BrowserSessionsTest.php`

- `test_other_browser_sessions_can_be_logged_out`

#### `CreateApiTokenTest` (1 tests)
> `tests/Feature/CreateApiTokenTest.php`

- `test_api_tokens_can_be_created`

#### `CreateTeamTest` (1 tests)
> `tests/Feature/CreateTeamTest.php`

- `test_teams_can_be_created`

#### `DashboardTest` (10 tests)
> `tests/Feature/DashboardTest.php`

- `test_admin_empty_state_no_programs`
- `test_admin_sees_admin_dashboard_role`
- `test_admin_sees_charts_section`
- `test_dashboard_renders_for_admin`
- `test_dashboard_shows_real_stats`
- `test_guest_redirected`
- `test_operador_does_not_see_admin_widgets`
- `test_operador_no_pendientes_empty_state`
- `test_operador_sees_pendientes_widget`
- `test_vencidos_widget_shown_when_overdue`

#### `DeleteAccountTest` (2 tests)
> `tests/Feature/DeleteAccountTest.php`

- `test_correct_password_must_be_provided_before_account_can_be_deleted`
- `test_user_accounts_can_be_deleted`

#### `DeleteApiTokenTest` (1 tests)
> `tests/Feature/DeleteApiTokenTest.php`

- `test_api_tokens_can_be_deleted`

#### `DeleteTeamTest` (2 tests)
> `tests/Feature/DeleteTeamTest.php`

- `test_personal_teams_cant_be_deleted`
- `test_teams_can_be_deleted`

#### `EmailVerificationTest` (3 tests)
> `tests/Feature/EmailVerificationTest.php`

- `test_email_can_be_verified`
- `test_email_can_not_verified_with_invalid_hash`
- `test_email_verification_screen_can_be_rendered`

#### `ExampleTest` (1 tests)
> `tests/Feature/ExampleTest.php`

- `test_the_application_returns_a_successful_response`

#### `InviteTeamMemberTest` (2 tests)
> `tests/Feature/InviteTeamMemberTest.php`

- `test_team_member_invitations_can_be_cancelled`
- `test_team_members_can_be_invited_to_team`

#### `JetstreamCustomizationTest` (10 tests)
> `tests/Feature/JetstreamCustomizationTest.php`

- `test_forgot_password_renders_in_spanish`
- `test_login_page_does_not_show_register_link`
- `test_login_page_renders`
- `test_operador_cannot_see_create_team`
- `test_profile_does_not_show_delete_account`
- `test_profile_shows_spanish_labels`
- `test_register_post_is_disabled`
- `test_register_route_is_disabled`
- `test_team_settings_shows_ur_terminology`
- `test_topbar_shows_role_and_ur`

#### `LayoutTest` (9 tests)
> `tests/Feature/LayoutTest.php`

- `test_admin_sees_all_navigation_sections`
- `test_guest_redirected_to_login`
- `test_inter_font_loaded`
- `test_operador_sees_limited_navigation`
- `test_planeador_sees_planning_and_catalogs`
- `test_sidebar_renders_for_authenticated_user`
- `test_sidebar_shows_team_name`
- `test_sidebar_shows_user_name`
- `test_topbar_renders_with_user_dropdown`

#### `LeaveTeamTest` (2 tests)
> `tests/Feature/LeaveTeamTest.php`

- `test_team_owners_cant_leave_their_own_team`
- `test_users_can_leave_teams`

#### `MatrizAlineacionTest` (9 tests)
> `tests/Feature/MatrizAlineacionTest.php`

- `test_crear_alineacion_linea_programa`
- `test_crear_alineacion_ped_pnd`
- `test_crear_alineacion_pnd_ods`
- `test_eager_loading_evita_n1`
- `test_eliminar_alineacion_ped_pnd`
- `test_herencia_de_cadena_completa`
- `test_usuario_con_permiso_puede_acceder`
- `test_usuario_sin_permiso_no_puede_acceder`
- `test_ver_cadena_completa_modal`

#### `PasswordConfirmationTest` (3 tests)
> `tests/Feature/PasswordConfirmationTest.php`

- `test_confirm_password_screen_can_be_rendered`
- `test_password_can_be_confirmed`
- `test_password_is_not_confirmed_with_invalid_password`

#### `PasswordResetTest` (4 tests)
> `tests/Feature/PasswordResetTest.php`

- `test_password_can_be_reset_with_valid_token`
- `test_reset_password_link_can_be_requested`
- `test_reset_password_link_screen_can_be_rendered`
- `test_reset_password_screen_can_be_rendered`

#### `PedCrudTest` (14 tests)
> `tests/Feature/PedCrudTest.php`

- `test_crear_eje_desde_livewire`
- `test_crear_nodo_con_tipo_invalido_retorna_400`
- `test_crear_plan_desde_livewire`
- `test_editar_plan_desde_livewire`
- `test_eliminar_eje_elimina_temas_cascade`
- `test_eliminar_plan_desde_livewire`
- `test_no_puede_haber_dos_planes_activos`
- `test_puede_acceder_pagina_crear_nodo`
- `test_puede_acceder_pagina_crear_plan`
- `test_puede_acceder_pagina_editar_plan`
- `test_usuario_con_permiso_puede_acceder`
- `test_usuario_sin_permiso_no_puede_acceder`
- `test_validacion_descripcion_max_500_caracteres`
- `test_validacion_periodo_fin_mayor_que_inicio`

#### `PedPlanActivoConstraintTest` (3 tests)
> `tests/Feature/PedPlanActivoConstraintTest.php`

- `test_metodo_activar_desactiva_los_demas`
- `test_no_pueden_existir_dos_planes_activos`
- `test_si_pueden_existir_multiples_planes_inactivos`

#### `ProfileInformationTest` (2 tests)
> `tests/Feature/ProfileInformationTest.php`

- `test_current_profile_information_is_available`
- `test_profile_information_can_be_updated`

#### `ProgramaPresupuestarioMmlTest` (6 tests)
> `tests/Feature/ProgramaPresupuestarioMmlTest.php`

- `test_cast_enums`
- `test_programa_has_mml_fields`
- `test_relacion_creador`
- `test_relacion_team`
- `test_scope_ejercicio`
- `test_scope_para_team`

#### `ProgramasDerivadosCrudTest` (13 tests)
> `tests/Feature/ProgramasDerivadosCrudTest.php`

- `test_crear_objetivo_en_programa`
- `test_crear_programa_derivado`
- `test_editar_programa_derivado`
- `test_eliminar_objetivo`
- `test_eliminar_programa_derivado`
- `test_eliminar_programa_elimina_objetivos`
- `test_filtro_por_tipo_sectorial`
- `test_sin_ped_activo_muestra_advertencia`
- `test_usuario_con_permiso_puede_acceder`
- `test_usuario_sin_permiso_no_puede_acceder`
- `test_validacion_descripcion_objetivo_max_500`
- `test_validacion_tipo_invalido`
- `test_validacion_tipo_requerido`

#### `ProgramasDerivadosEnumTest` (5 tests)
> `tests/Feature/ProgramasDerivadosEnumTest.php`

- `test_enum_php_contiene_los_cuatro_tipos`
- `test_modelo_castea_enum_correctamente`
- `test_no_permite_valor_invalido_en_enum`
- `test_prefijo_clave_por_tipo`
- `test_scope_sectoriales_filtra_correctamente`

#### `RegistrationTest` (3 tests)
> `tests/Feature/RegistrationTest.php`

- `test_new_users_can_register`
- `test_registration_screen_can_be_rendered`
- `test_registration_screen_cannot_be_rendered_if_support_is_disabled`

#### `RemoveTeamMemberTest` (2 tests)
> `tests/Feature/RemoveTeamMemberTest.php`

- `test_only_team_owner_can_remove_team_members`
- `test_team_members_can_be_removed_from_teams`

#### `RolesAndPermissionsTest` (3 tests)
> `tests/Feature/RolesAndPermissionsTest.php`

- `test_admin_tiene_todos_los_permisos`
- `test_operador_no_tiene_permiso_crear_programa`
- `test_planeador_tiene_permiso_crear_programa`

#### `SecurityHeadersTest` (2 tests)
> `tests/Feature/SecurityHeadersTest.php`

- `test_csp_header_blocks_frame_ancestors`
- `test_security_headers_are_present`

#### `TwoFactorAuthenticationSettingsTest` (3 tests)
> `tests/Feature/TwoFactorAuthenticationSettingsTest.php`

- `test_recovery_codes_can_be_regenerated`
- `test_two_factor_authentication_can_be_disabled`
- `test_two_factor_authentication_can_be_enabled`

#### `UpdatePasswordTest` (3 tests)
> `tests/Feature/UpdatePasswordTest.php`

- `test_current_password_must_be_correct`
- `test_new_passwords_must_match`
- `test_password_can_be_updated`

#### `UpdateTeamMemberRoleTest` (2 tests)
> `tests/Feature/UpdateTeamMemberRoleTest.php`

- `test_only_team_owner_can_update_team_member_roles`
- `test_team_member_roles_can_be_updated`

#### `UpdateTeamNameTest` (1 tests)
> `tests/Feature/UpdateTeamNameTest.php`

- `test_team_names_can_be_updated`

---

### Feature — Administración y Auditoría (33 tests)

#### `AuditEvaluacionTest` (2 tests)
> `tests/Feature/Admin/AuditEvaluacionTest.php`

- `test_evaluacion_creation_is_logged`
- `test_evaluacion_update_excludes_json_fields`

#### `AuditMirTest` (3 tests)
> `tests/Feature/Admin/AuditMirTest.php`

- `test_indicador_creation_is_logged`
- `test_mir_nivel_creation_is_logged`
- `test_mir_nivel_update_logs_only_changed`

#### `AuditoriaComponentTest` (8 tests)
> `tests/Feature/Admin/AuditoriaComponentTest.php`

- `test_activities_are_displayed`
- `test_admin_can_access_auditoria`
- `test_filter_by_date_range`
- `test_filter_by_event`
- `test_filter_by_subject_type`
- `test_guest_is_redirected`
- `test_limpiar_filtros_resets_all`
- `test_non_admin_cannot_access_auditoria`

#### `AuditPedTest` (5 tests)
> `tests/Feature/Admin/AuditPedTest.php`

- `test_ped_eje_creation_is_logged`
- `test_ped_objetivo_estrategico_creation_is_logged`
- `test_ped_plan_creation_is_logged`
- `test_ped_plan_update_logs_only_dirty`
- `test_ped_tema_creation_is_logged`

#### `AuditUserTest` (4 tests)
> `tests/Feature/Admin/AuditUserTest.php`

- `test_password_is_not_logged`
- `test_user_activation_change_is_logged`
- `test_user_creation_is_logged`
- `test_user_update_logs_only_dirty_attributes`

#### `MonitoreoIaTest` (11 tests)
> `tests/Feature/Admin/MonitoreoIaTest.php`

- `test_403_without_administrar_usuarios_permission`
- `test_budget_alert_triggers_when_threshold_exceeded`
- `test_budget_percent_used_calculation`
- `test_cleanup_command_respects_days_flag`
- `test_cleanup_dry_run_does_not_delete`
- `test_cost_estimation`
- `test_dashboard_renders_with_metrics`
- `test_probar_conexion_requires_admin`
- `test_probar_conexion_returns_error_on_api_failure`
- `test_probar_conexion_returns_success_result`
- `test_usage_by_type_shows_correct_grouping`

---

### Feature — Cascade / PED (4 tests)

#### `PedImporterTest` (4 tests)
> `tests/Feature/Cascade/PedImporterTest.php`

- `test_desactiva_plan_anterior_al_importar_nuevo_activo`
- `test_importa_archivo_valido`
- `test_usuario_con_permiso_puede_acceder`
- `test_usuario_sin_permiso_no_puede_acceder`

---

### Feature — Componentes UI (10 tests)

#### `HelpLabelComponentTest` (4 tests)
> `tests/Feature/Components/HelpLabelComponentTest.php`

- `help_label_renders_for_attribute`
- `help_label_renders_with_custom_help_text`
- `help_label_renders_with_glossary_key`
- `help_label_renders_without_tooltip_when_no_help`

#### `StepperComponentTest` (2 tests)
> `tests/Feature/Components/StepperComponentTest.php`

- `test_stepper_marks_current_step_as_active`
- `test_stepper_renders_six_steps`

#### `TooltipComponentTest` (4 tests)
> `tests/Feature/Components/TooltipComponentTest.php`

- `tooltip_renders_with_custom_max_width`
- `tooltip_renders_with_right_position`
- `tooltip_renders_with_text`
- `tooltip_renders_with_top_position`

---

### Feature — Dashboard (4 tests)

#### `AdminDashboardTest` (2 tests)
> `tests/Feature/Dashboard/AdminDashboardTest.php`

- `test_admin_sees_global_kpis_and_admin_links`
- `test_operador_does_not_see_admin_links`

#### `PlaneadorDashboardTest` (2 tests)
> `tests/Feature/Dashboard/PlaneadorDashboardTest.php`

- `test_avances_por_revisar_returns_only_en_revision`
- `test_planeador_sees_planeador_dashboard`

---

### Feature — Embeddings / IA (20 tests)

#### `EmbeddingObserverTest` (7 tests)
> `tests/Feature/Embeddings/EmbeddingObserverTest.php`

- `test_actualizar_descripcion_despacha_job`
- `test_actualizar_otro_campo_no_despacha_job`
- `test_crear_ods_meta_despacha_job`
- `test_crear_ods_objetivo_despacha_job`
- `test_crear_registro_sin_descripcion_no_despacha_job`
- `test_job_se_despacha_a_cola_embeddings`
- `test_observers_deshabilitados_no_despachan_job`

#### `EmbeddingsGenerateTest` (10 tests)
> `tests/Feature/Embeddings/EmbeddingsGenerateTest.php`

- `test_chunk_size_controls_batch_processing`
- `test_command_processes_records_with_null_embeddings`
- `test_force_flag_regenerates_all_embeddings`
- `test_get_embeddable_text_returns_combined_fields`
- `test_has_embedding_trait_needs_embedding_scope`
- `test_invalid_table_returns_failure`
- `test_retry_logic_on_api_failure`
- `test_skips_records_with_empty_embeddable_text`
- `test_summary_report_is_displayed`
- `test_table_flag_limits_processing_to_specific_table`

#### `HnswIndexesTest` (3 tests)
> `tests/Feature/Embeddings/HnswIndexesTest.php`

- `test_indices_hnsw_creados_correctamente`
- `test_indices_tienen_parametros_correctos`
- `test_indices_usan_hnsw`

---

### Feature — Evaluación (43 tests)

#### `DatosAbiertosTest` (7 tests)
> `tests/Feature/Evaluation/DatosAbiertosTest.php`

- `test_403_sin_permiso_exportar_reportes`
- `test_controller_csv_descarga_correctamente`
- `test_controller_json_descarga_correctamente`
- `test_csv_export_es_utf8_con_columnas_correctas`
- `test_diccionario_documenta_todas_las_columnas`
- `test_json_export_tiene_metadata_y_estructura_data`
- `test_zip_contiene_tres_archivos`

#### `EtiquetadoAnexosTest` (3 tests)
> `tests/Feature/Evaluation/EtiquetadoAnexosTest.php`

- `test_badges_en_panel_seguimiento`
- `test_indicador_sin_anexos`
- `test_sync_anexos_a_indicador`

#### `EvaluacionProgramaViewTest` (5 tests)
> `tests/Feature/Evaluation/EvaluacionProgramaViewTest.php`

- `test_indicadores_cronicos`
- `test_renderiza_evaluacion`
- `test_requiere_permiso`
- `test_sin_evaluacion_anterior`
- `test_tendencia_mejoro`

#### `ExportacionTest` (6 tests)
> `tests/Feature/Evaluation/ExportacionTest.php`

- `test_403_sin_permiso_exportar_reportes`
- `test_descarga_pdf_con_permiso`
- `test_despacha_job_async`
- `test_genera_excel_avance_trimestral`
- `test_genera_pdf_mir`
- `test_pdf_contiene_encabezado_y_periodo`

#### `IndiceEficaciaTest` (6 tests)
> `tests/Feature/Evaluation/IndiceEficaciaTest.php`

- `test_activo_seguimiento_false_excluido`
- `test_calcula_indice_basico`
- `test_conteo_semaforos_correcto`
- `test_excluye_indicadores_sin_capturas`
- `test_pesos_desde_config`
- `test_solo_avances_aprobados`

#### `LogicaVerticalTest` (4 tests)
> `tests/Feature/Evaluation/LogicaVerticalTest.php`

- `test_genera_analisis_con_mock`
- `test_guarda_analisis_en_evaluacion`
- `test_mock_cuando_no_hay_api_key`
- `test_retorna_null_si_llm_falla`

#### `ModelosEvaluacionTest` (6 tests)
> `tests/Feature/Evaluation/ModelosEvaluacionTest.php`

- `test_anexo_scope_activos`
- `test_crear_anexo_transversal`
- `test_crear_evaluacion_programa`
- `test_evaluacion_relacion_programa`
- `test_indicador_anexos_transversales_m2m`
- `test_seeder_crea_4_anexos`

#### `PanelTransversalTest` (6 tests)
> `tests/Feature/Evaluation/PanelTransversalTest.php`

- `test_anexo_tab_shows_indicators_by_theme`
- `test_ods_tab_renders`
- `test_renders_ped_tab_by_default`
- `test_requires_exportar_reportes_permission`
- `test_ur_tab_shows_team_ranking`
- `test_warning_shown_for_programs_without_ped_alignment`

---

### Feature — Exportaciones / Reportes (22 tests)

#### `AvanceTrimestralExportTest` (4 tests)
> `tests/Feature/Exports/AvanceTrimestralExportTest.php`

- `test_avance_trimestral_pdf_contains_vobo`
- `test_avance_trimestral_pdf_downloads_for_planeador`
- `test_avance_trimestral_pdf_endpoint_requires_permission`
- `test_avance_trimestral_pdf_generates_with_vobo`

#### `ConcentradoCapturaTest` (5 tests)
> `tests/Feature/Exports/ConcentradoCapturaTest.php`

- `test_admin_sees_all_teams`
- `test_concentrado_accessible_by_operador`
- `test_concentrado_filters_by_date_range`
- `test_concentrado_forbidden_without_permission`
- `test_concentrado_renders_with_metrics`

#### `FmyeExportTest` (4 tests)
> `tests/Feature/Exports/FmyeExportTest.php`

- `test_fmye_endpoint_forbidden_without_permission`
- `test_fmye_endpoint_works_for_planeador`
- `test_fmye_pdf_contains_all_sections`
- `test_fmye_pdf_generates_successfully`

#### `MirAprobadaExportTest` (3 tests)
> `tests/Feature/Exports/MirAprobadaExportTest.php`

- `test_mir_pdf_contains_transparency_footer`
- `test_mir_publica_route_accessible_by_any_authenticated_user`
- `test_mir_publica_route_requires_authentication`

#### `SabanaCapturaTest` (6 tests)
> `tests/Feature/Exports/SabanaCapturaTest.php`

- `test_admin_sees_all_teams_in_sabana`
- `test_sabana_captura_accessible_by_operador`
- `test_sabana_captura_accessible_by_planeador`
- `test_sabana_captura_filters_by_trimestre`
- `test_sabana_captura_forbidden_without_permission`
- `test_sabana_captura_renders_livewire_component`

---

### Feature — MML (Planeación) (172 tests)

#### `AlineacionEstrategicaTest` (7 tests)
> `tests/Feature/Mml/AlineacionEstrategicaTest.php`

- `test_carga_alineacion_existente_al_montar`
- `test_componente_se_renderiza`
- `test_finalizar_planeacion_creates_mir_and_redirects`
- `test_no_permite_guardar_sin_objetivo`
- `test_puede_guardar_alineacion_con_ped`
- `test_seleccionar_objetivo_carga_relaciones_pnd_ods`
- `test_selects_dependientes_se_limpian_al_cambiar_eje`

#### `AlineacionMirTest` (6 tests)
> `tests/Feature/Mml/AlineacionMirTest.php`

- `test_buscar_alineacion_retorna_lineas_accion_para_componente`
- `test_buscar_alineacion_retorna_sugerencias_para_fin`
- `test_buscar_alineacion_sin_resumen_no_busca`
- `test_seleccionar_alineacion_guarda_fk_linea_accion`
- `test_seleccionar_alineacion_guarda_fk_objetivo_estrategico`
- `test_seleccionar_alineacion_limpia_sugerencias`

#### `AlternativaTest` (5 tests)
> `tests/Feature/Mml/AlternativaTest.php`

- `test_alternativa_pertenece_a_programa`
- `test_asignar_nodos_a_alternativa`
- `test_cascade_delete_programa_elimina_alternativas`
- `test_crear_alternativa`
- `test_seleccionar_alternativa_con_justificacion`

#### `ArbolObjetivosBuilderTest` (5 tests)
> `tests/Feature/Mml/ArbolObjetivosBuilderTest.php`

- `test_componente_se_renderiza`
- `test_editar_nodo_transformado`
- `test_genera_arbol_objetivos_automaticamente`
- `test_nodos_vinculados_via_nodo_origen_id`
- `test_no_regenera_si_arbol_objetivos_ya_existe`

#### `ArbolProblemaBuilderTest` (12 tests)
> `tests/Feature/Mml/ArbolProblemaBuilderTest.php`

- `test_agregar_causa_directa`
- `test_agregar_causa_indirecta_bajo_causa_directa`
- `test_agregar_efecto_directo`
- `test_componente_se_renderiza_con_problema_central`
- `test_confirmar_arbol_ejemplo_persiste_nodos`
- `test_editar_nodo`
- `test_eliminar_nodo`
- `test_generar_arbol_ejemplo_produce_preview_sin_persistir`
- `test_no_puede_eliminar_problema_central`
- `test_sugerir_causas_agrega_como_causa_directa`
- `test_sugerir_causas_indirectas_genera_sugerencias_para_causa_directa`
- `test_sugerir_efectos_agrega_como_efecto_directo`

#### `ArbolTest` (9 tests)
> `tests/Feature/Mml/ArbolTest.php`

- `test_arbol_pertenece_a_programa`
- `test_arbol_tiene_nodos`
- `test_cascade_delete_arbol_elimina_nodos`
- `test_cast_tipo_nodo_a_enum`
- `test_crear_arbol_de_problemas`
- `test_crear_nodo_problema_central`
- `test_relacion_recursiva_parent_children`
- `test_unique_constraint_tipo_por_programa`
- `test_vinculo_nodo_origen_problema_a_objetivo`

#### `CalendarizacionTest` (6 tests)
> `tests/Feature/Mml/CalendarizacionTest.php`

- `test_confirmar_persiste_metas_periodo`
- `test_genera_12_periodos_para_mensual`
- `test_genera_1_periodo_para_anual`
- `test_genera_4_periodos_para_trimestral`
- `test_ignora_indicadores_inactivos`
- `test_ignora_indicadores_sin_meta`

#### `CompletarHuecosTest` (3 tests)
> `tests/Feature/Mml/CompletarHuecosTest.php`

- `test_corregir_campo_updates_and_reactivates`
- `test_finalizar_changes_estado`
- `test_persists_on_mount_when_no_programa`

#### `DashboardImportacionesTest` (2 tests)
> `tests/Feature/Mml/DashboardImportacionesTest.php`

- `test_muestra_reportes_del_equipo`
- `test_no_muestra_reportes_de_otro_equipo`

#### `DefinicionProblemaTest` (6 tests)
> `tests/Feature/Mml/DefinicionProblemaTest.php`

- `test_actualiza_problema_existente_al_guardar`
- `test_carga_problema_existente`
- `test_componente_se_renderiza`
- `test_guardar_problema_central`
- `test_validacion_descripcion_minimo_caracteres`
- `test_validacion_descripcion_requerida`

#### `EmbudoPoblacionesTest` (6 tests)
> `tests/Feature/Mml/EmbudoPoblacionesTest.php`

- `test_carga_datos_existentes_al_montar`
- `test_componente_se_renderiza`
- `test_puede_guardar_poblaciones_validas`
- `test_requiere_campos_obligatorios`
- `test_valida_embudo_objetivo_menor_potencial`
- `test_valida_embudo_potencial_menor_referencia`

#### `ExtraccionVariablesTest` (7 tests)
> `tests/Feature/Mml/ExtraccionVariablesTest.php`

- `test_agregar_variable_manual`
- `test_eliminar_variable`
- `test_extraer_variables_crea_registros`
- `test_guardar_variable`
- `test_no_extrae_sin_formula`
- `test_reextraccion_limpia_variables_anteriores`
- `test_variables_tienen_simbolo_y_nombre`

#### `ImportarProgramaTest` (4 tests)
> `tests/Feature/Mml/ImportarProgramaTest.php`

- `test_component_renders_successfully`
- `test_upload_csv_file_parses_successfully`
- `test_upload_md_file_parses_and_shows_preview`
- `test_upload_unsupported_extension_shows_error`

#### `IndicadorTest` (8 tests)
> `tests/Feature/Mml/IndicadorTest.php`

- `test_cascade_delete_mir_nivel_elimina_indicadores`
- `test_cast_enums`
- `test_catalogo_unidades_medida`
- `test_crear_indicador_con_todos_los_campos`
- `test_relacion_indicador_cremaa_validacion`
- `test_relacion_indicador_medios_verificacion`
- `test_relacion_indicador_mir_nivel`
- `test_relacion_indicador_variables`

#### `MirDiagnosticoTest` (6 tests)
> `tests/Feature/Mml/MirDiagnosticoTest.php`

- `test_complete_mir_has_no_critical_gaps`
- `test_conteo_counts_by_severity`
- `test_missing_formula_is_critical`
- `test_missing_resumen_narrativo_is_critical`
- `test_missing_sentido_is_minor`
- `test_unrecognized_enum_value_is_warning`

#### `MirEditorTest` (13 tests)
> `tests/Feature/Mml/MirEditorTest.php`

- `test_aceptar_sugerencia_limpia_estado_validacion`
- `test_agregar_actividad_bajo_componente`
- `test_agregar_componente`
- `test_componente_se_renderiza`
- `test_editar_resumen_narrativo`
- `test_eliminar_actividad`
- `test_guardar_supuestos`
- `test_mir_editor_defaults_to_read_mode`
- `test_no_prellenar_si_mir_tiene_niveles`
- `test_prellenado_genera_niveles_desde_eap`
- `test_puede_ver_version_historica_en_modo_lectura`
- `test_sugerir_formula_genera_formula_para_indicador`
- `test_toggle_editar_nivel_activa_modo_edicion`

#### `MirNivelTest` (7 tests)
> `tests/Feature/Mml/MirNivelTest.php`

- `test_cascade_delete`
- `test_cast_tipo_nivel_enum`
- `test_crear_mir_version_snapshot`
- `test_crear_nivel_fin`
- `test_jerarquia_componente_actividad`
- `test_relacion_programa`
- `test_relacion_programa_mir_niveles`

#### `MirParserTest` (10 tests)
> `tests/Feature/Mml/MirParserTest.php`

- `test_parse_csv_extracts_all_niveles`
- `test_parse_csv_handles_missing_fields`
- `test_parse_markdown_assigns_componente_idx_to_actividades`
- `test_parse_markdown_extracts_all_niveles`
- `test_parse_markdown_extracts_header_metadata`
- `test_parse_markdown_extracts_indicadores`
- `test_parse_markdown_extracts_medios_verificacion`
- `test_parse_markdown_normalizes_accented_tipo_nivel`
- `test_parse_markdown_with_empty_content`
- `test_to_array_and_from_array_round_trip`

#### `MirPersistenciaTest` (5 tests)
> `tests/Feature/Mml/MirPersistenciaTest.php`

- `test_creates_indicadores_and_medios`
- `test_creates_niveles_with_hierarchy`
- `test_creates_programa_importado`
- `test_critical_gaps_disable_seguimiento`
- `test_maps_accented_enums`

#### `MirSnapshotTest` (7 tests)
> `tests/Feature/Mml/MirSnapshotTest.php`

- `test_crear_snapshot_guarda_estado_completo`
- `test_crear_snapshot_via_livewire`
- `test_no_crea_snapshot_sin_etiqueta`
- `test_restaurar_mantiene_jerarquia_componente_actividad`
- `test_restaurar_recrea_indicadores`
- `test_restaurar_version_reemplaza_mir`
- `test_snapshot_incluye_indicadores_y_variables`

#### `SeleccionAlternativasTest` (8 tests)
> `tests/Feature/Mml/SeleccionAlternativasTest.php`

- `test_asignar_nodos_a_alternativa`
- `test_componente_se_renderiza`
- `test_crear_alternativa`
- `test_eliminar_alternativa`
- `test_justificacion_requerida_para_seleccionar`
- `test_muestra_medios_del_arbol_objetivos`
- `test_seleccionar_alternativa_con_justificacion`
- `test_solo_una_alternativa_seleccionada`

#### `StoreIndicadorRequestTest` (6 tests)
> `tests/Feature/Mml/StoreIndicadorRequestTest.php`

- `test_actividad_solo_acepta_gestion`
- `test_componente_acepta_estrategico_y_gestion`
- `test_economia_en_nivel_fin_falla`
- `test_estrategico_en_nivel_fin_pasa`
- `test_gestion_en_nivel_fin_falla`
- `test_mensual_en_nivel_fin_falla`

#### `UrCoadyuvanteTest` (6 tests)
> `tests/Feature/Mml/UrCoadyuvanteTest.php`

- `test_asignar_ur_actualiza_team_id`
- `test_asignar_ur_crea_programa_team`
- `test_cambiar_ur_limpia_anterior`
- `test_no_asigna_ur_a_fin`
- `test_quitar_ur_elimina_de_programa_team_si_no_tiene_otros`
- `test_quitar_ur_mantiene_programa_team_si_tiene_otros`

#### `ValidacionCremaaTest` (5 tests)
> `tests/Feature/Mml/ValidacionCremaaTest.php`

- `test_actualiza_validacion_existente`
- `test_no_bloquea_guardado_de_indicador`
- `test_observaciones_se_guardan`
- `test_resultado_tiene_6_campos_boolean`
- `test_validar_cremaa_crea_registro`

#### `ValidacionLogicaTest` (4 tests)
> `tests/Feature/Mml/ValidacionLogicaTest.php`

- `test_hallazgos_clasificados_por_severidad`
- `test_no_bloquea_guardado`
- `test_servicio_retorna_estructura_correcta`
- `test_validar_mir_genera_reporte_con_hallazgos`

#### `ValidacionSintaxisTest` (5 tests)
> `tests/Feature/Mml/ValidacionSintaxisTest.php`

- `test_aceptar_sugerencia_actualiza_resumen`
- `test_resultado_se_persiste_con_timestamp`
- `test_sugerencia_de_reescritura_disponible`
- `test_validacion_no_bloquea_guardado`
- `test_validar_sintaxis_fin_retorna_resultado`

#### `VincularAlineacionImportTest` (4 tests)
> `tests/Feature/Mml/VincularAlineacionImportTest.php`

- `test_componente_se_renderiza`
- `test_finalizar_redirige`
- `test_omitir_avanza_paso`
- `test_seleccionar_persiste_fk`

---

### Feature — Notificaciones (8 tests)

#### `NotificationBellTest` (4 tests)
> `tests/Feature/Notifications/NotificationBellTest.php`

- `test_bell_renders_for_authenticated_user`
- `test_bell_shows_unread_count`
- `test_mark_all_as_read`
- `test_mark_single_as_read`

#### `NotificationsIndexTest` (4 tests)
> `tests/Feature/Notifications/NotificationsIndexTest.php`

- `test_filter_unread_only`
- `test_guest_cannot_access_notifications`
- `test_mark_as_read`
- `test_notifications_page_renders`

---

### Feature — Presupuesto (23 tests)

#### `ModelosPresupuestoTest` (23 tests)
> `tests/Feature/Presupuesto/ModelosPresupuestoTest.php`

- `test_avance_check_devengado_le_comprometido`
- `test_avance_check_pagado_le_devengado`
- `test_avance_check_trimestre_rango`
- `test_avance_financiero_pertenece_a_partida`
- `test_avance_financiero_unique_por_trimestre`
- `test_meta_gasto_check_monto_positivo`
- `test_meta_gasto_check_trimestre_rango`
- `test_meta_gasto_pertenece_a_partida`
- `test_meta_gasto_unique_por_trimestre`
- `test_partida_casts_montos_correctamente`
- `test_partida_monto_efectivo_usa_aprobado_sin_modificado`
- `test_partida_monto_efectivo_usa_modificado_cuando_existe`
- `test_partida_pertenece_a_programa`
- `test_partida_pertenece_a_team`
- `test_partida_porcentaje_ejercido_con_avances`
- `test_partida_porcentaje_ejercido_sin_avances`
- `test_partida_scope_para_ejercicio`
- `test_partida_scope_para_team`
- `test_partida_tiene_avances_financieros`
- `test_partida_tiene_metas_gasto`
- `test_partida_tiene_registrador`
- `test_partida_unique_constraint`
- `test_programa_tiene_partidas_presupuestales`

---

### Feature — Seeders (12 tests)

#### `QaTestingSeederTest` (12 tests)
> `tests/Feature/Seeders/QaTestingSeederTest.php`

- `test_avances_created_with_expected_states`
- `test_creates_four_programs`
- `test_indicators_created_with_correct_frequencies`
- `test_meta_periodos_generated`
- `test_mir_levels_created_for_each_program`
- `test_programs_belong_to_correct_teams`
- `test_seeder_creates_six_users`
- `test_seeder_is_idempotent`
- `test_semaforo_colors_present`
- `test_users_assigned_to_correct_teams`
- `test_users_have_correct_roles`
- `test_vencido_meta_exists`

---

### Feature — Seguimiento (65 tests)

#### `AvanceEstadoTest` (10 tests)
> `tests/Feature/Tracking/AvanceEstadoTest.php`

- `test_aprobado_no_permite_transicion`
- `test_aprobar_congela_avance`
- `test_historial_se_acumula`
- `test_notificacion_al_observar`
- `test_transicion_en_captura_a_en_revision`
- `test_transicion_en_revision_a_aprobado`
- `test_transicion_en_revision_a_observado`
- `test_transicion_invalida_lanza_excepcion`
- `test_transicion_observado_a_en_captura`
- `test_vencido_no_permite_transicion`

#### `CalendarioTest` (8 tests)
> `tests/Feature/Tracking/CalendarioTest.php`

- `test_abrir_periodos_crea_avances`
- `test_abrir_periodos_no_duplica`
- `test_calcular_fechas_mensual`
- `test_calcular_fechas_semestral`
- `test_calcular_fechas_trimestral`
- `test_cerrar_vencidos_marca_estado`
- `test_cerrar_vencidos_no_afecta_aprobados`
- `test_notificacion_periodo_abierto`

#### `CapturaAvanceTest` (3 tests)
> `tests/Feature/Tracking/CapturaAvanceTest.php`

- `test_guardar_avance`
- `test_no_editable_si_congelado`
- `test_renderiza_formulario`

#### `DashboardIndicadoresTest` (4 tests)
> `tests/Feature/Tracking/DashboardIndicadoresTest.php`

- `test_empty_state_shown_without_levels`
- `test_renders_hierarchical_mir_structure`
- `test_toggle_nivel_collapses_when_already_expanded`
- `test_toggle_nivel_expands_and_collapses`

#### `DesbloqueoTest` (7 tests)
> `tests/Feature/Tracking/DesbloqueoTest.php`

- `test_aprobar_descongela`
- `test_avance_congelado_bloquea_edicion`
- `test_historial_actualizado_al_aprobar`
- `test_no_duplica_solicitud_pendiente`
- `test_rechazar_mantiene_congelado`
- `test_solicitar_desbloqueo`
- `test_solo_admin_puede_aprobar`

#### `EvidenciaTest` (6 tests)
> `tests/Feature/Tracking/EvidenciaTest.php`

- `test_archivo_en_disco_privado`
- `test_capturador_puede_descargar`
- `test_download_con_permiso`
- `test_download_sin_permiso_denegado`
- `test_eliminar_solo_si_no_congelado`
- `test_upload_crea_evidencia_con_hash`

#### `FormulaEvaluatorTest` (4 tests)
> `tests/Feature/Tracking/FormulaEvaluatorTest.php`

- `test_evalua_formula_basica`
- `test_evalua_formula_con_x`
- `test_formula_division_por_cero`
- `test_formula_invalida`

#### `JustificacionTest` (5 tests)
> `tests/Feature/Tracking/JustificacionTest.php`

- `test_genera_justificacion_con_supuestos`
- `test_genera_justificacion_sin_supuestos`
- `test_guarda_justificacion_ia_y_final`
- `test_incluye_historial`
- `test_retorna_null_si_llm_falla`

#### `ModelosTrackingTest` (10 tests)
> `tests/Feature/Tracking/ModelosTrackingTest.php`

- `test_avance_congelado`
- `test_avance_evidencia`
- `test_avance_relaciones`
- `test_avance_variable_con_acumulado`
- `test_comportamiento_variable_enum`
- `test_crear_avance_con_estado`
- `test_desbloqueo`
- `test_estado_avance_enum`
- `test_indicador_tiene_avances`
- `test_meta_periodo_tiene_avance`

#### `PanelSeguimientoTest` (5 tests)
> `tests/Feature/Tracking/PanelSeguimientoTest.php`

- `test_aislamiento_multi_ur`
- `test_estado_vacio`
- `test_filtro_por_estado`
- `test_filtro_por_programa`
- `test_requiere_permiso_revisar_avance`

#### `SemaforoTest` (3 tests)
> `tests/Feature/Tracking/SemaforoTest.php`

- `test_semaforo_ascendente_amarillo`
- `test_semaforo_ascendente_verde`
- `test_semaforo_rojo_bajo_umbral`

---

### Feature — Usuarios e Invitaciones (31 tests)

#### `EnsureUserIsActivatedTest` (4 tests)
> `tests/Feature/UserInvitation/EnsureUserIsActivatedTest.php`

- `test_activated_user_can_access_dashboard`
- `test_guest_is_not_affected`
- `test_inactive_user_is_logged_out`
- `test_pending_user_is_redirected`

#### `GestionUsuariosTest` (7 tests)
> `tests/Feature/UserInvitation/GestionUsuariosTest.php`

- `test_admin_can_access_gestion_usuarios`
- `test_admin_cannot_deactivate_self`
- `test_admin_can_send_invitation`
- `test_admin_can_toggle_user_active`
- `test_invitation_requires_valid_email`
- `test_operador_cannot_access_gestion_usuarios`
- `test_table_shows_user_status`

#### `InvitacionUsuarioServiceTest` (4 tests)
> `tests/Feature/UserInvitation/InvitacionUsuarioServiceTest.php`

- `test_invitar_creates_user_with_token`
- `test_invitar_sends_email`
- `test_reenviar_generates_new_token`
- `test_reenviar_sends_email`

#### `InvitationFieldsTest` (6 tests)
> `tests/Feature/UserInvitation/InvitationFieldsTest.php`

- `test_invitation_expires_after_72_hours`
- `test_invitation_valid_within_72_hours`
- `test_invited_user_is_pending`
- `test_scope_active`
- `test_scope_pending_activation`
- `test_user_has_invitation_fields`

#### `InvitationPermissionTest` (3 tests)
> `tests/Feature/UserInvitation/InvitationPermissionTest.php`

- `test_admin_has_invitar_usuarios_permission`
- `test_operador_does_not_have_invitar_usuarios`
- `test_planeador_does_not_have_invitar_usuarios_by_default`

#### `OnboardingFlowTest` (7 tests)
> `tests/Feature/UserInvitation/OnboardingFlowTest.php`

- `test_2fa_page_redirects_if_no_password`
- `test_already_activated_token_is_invalid`
- `test_can_set_password`
- `test_expired_token_shows_error`
- `test_invalid_token_shows_error`
- `test_password_requires_confirmation`
- `test_valid_token_shows_password_form`

---

### Unit — Embeddings / IA (28 tests)

#### `EmbeddingServiceTest` (10 tests)
> `tests/Unit/Embeddings/EmbeddingServiceTest.php`

- `test_connection_error_generates_log`
- `test_genera_embedding_correctamente`
- `test_get_dimension`
- `test_get_model`
- `test_lanza_excepcion_si_api_falla`
- `test_lanza_excepcion_si_respuesta_dimension_incorrecta`
- `test_lanza_excepcion_si_respuesta_no_es_array`
- `test_lanza_excepcion_si_texto_solo_espacios`
- `test_lanza_excepcion_si_texto_vacio`
- `test_trunca_texto_largo`

#### `GenerateEmbeddingJobTest` (7 tests)
> `tests/Unit/Embeddings/GenerateEmbeddingJobTest.php`

- `test_job_failed_registra_error`
- `test_job_guarda_embedding_correctamente`
- `test_job_no_falla_si_modelo_no_existe`
- `test_job_tiene_backoff_configurado`
- `test_job_tiene_tags_correctos`
- `test_job_tiene_tries_configurado`
- `test_job_usa_cola_correcta`

#### `SemanticSearchServiceTest` (11 tests)
> `tests/Unit/Embeddings/SemanticSearchServiceTest.php`

- `test_dto_contiene_propiedades_correctas`
- `test_dto_get_percentage_attribute`
- `test_dto_is_high_quality`
- `test_find_similar_filtra_por_umbral`
- `test_find_similar_respeta_limite`
- `test_find_similar_retorna_resultados_ordenados_por_score`
- `test_get_searchable_models`
- `test_is_searchable`
- `test_lanza_excepcion_si_modelo_no_es_buscable`
- `test_lanza_excepcion_si_threshold_invalido`
- `test_retorna_coleccion_vacia_si_embedding_service_falla`

---

### Unit Tests (21 tests)

#### `ExampleTest` (1 tests)
> `tests/Unit/ExampleTest.php`

- `test_that_true_is_true`

#### `GlosarioConfigTest` (7 tests)
> `tests/Unit/GlosarioConfigTest.php`

- `all_values_are_non_empty_strings`
- `glosario_has_all_cremaa_keys`
- `glosario_has_all_mir_column_keys`
- `glosario_has_all_mir_level_keys`
- `glosario_has_logic_keys`
- `glosario_has_sentido_keys`
- `glosario_has_tree_keys`

#### `PedMarkdownParserTest` (7 tests)
> `tests/Unit/PedMarkdownParserTest.php`

- `test_detecta_error_sin_plan`
- `test_detecta_linea_sin_estrategia`
- `test_estadisticas_correctas`
- `test_extrae_periodo_con_guion_largo`
- `test_ignora_lineas_vacias`
- `test_parsea_jerarquia_completa`
- `test_parsea_plan_basico`

#### `PoblacionProgramaModelTest` (6 tests)
> `tests/Unit/PoblacionProgramaModelTest.php`

- `test_check_constraint_rechaza_cantidades_cero`
- `test_check_constraint_rechaza_objetivo_mayor_que_potencial`
- `test_check_constraint_rechaza_potencial_mayor_que_referencia`
- `test_puede_crear_poblacion_programa`
- `test_relacion_programa`
- `test_unique_constraint_por_programa_y_anio`

---

### Unit — LLM Service (23 tests)

#### `LlmServiceRefactorTest` (15 tests)
> `tests/Unit/Llm/LlmServiceRefactorTest.php`

- `test_cache_hit_logs_entry`
- `test_cache_hit_returns_cached_response`
- `test_cache_miss_calls_api_and_stores`
- `test_degraded_mode_on_api_error_returns_fallback`
- `test_degraded_mode_when_no_api_key_suggest`
- `test_degraded_mode_when_no_api_key_validate`
- `test_detect_causal_breaks_returns_null_in_degraded`
- `test_domain_method_validate_cremaa_delegates`
- `test_extract_variables_returns_empty_in_degraded`
- `test_generate_justification_returns_null_in_degraded_mode`
- `test_is_degraded_returns_false_with_key`
- `test_is_degraded_returns_true_when_no_key`
- `test_manifest_loaded_and_version_available`
- `test_suggest_alignment_returns_null_in_degraded`
- `test_transform_skips_cache`

#### `LlmServiceTest` (8 tests)
> `tests/Unit/Llm/LlmServiceTest.php`

- `test_handles_api_error_with_fallback_disabled`
- `test_handles_api_error_with_fallback_enabled`
- `test_implements_interface`
- `test_logs_error_on_failure`
- `test_suggest_logs_request`
- `test_suggest_returns_text`
- `test_transform_returns_transformed_text`
- `test_validate_returns_validation_result`

---

### Unit — MML (6 tests)

#### `IndicadorReglasServiceTest` (6 tests)
> `tests/Unit/Mml/IndicadorReglasServiceTest.php`

- `test_actividad_tipo_fijo_gestion`
- `test_componente_tipo_editable`
- `test_dimensiones_por_nivel`
- `test_fin_tipo_fijo_estrategico`
- `test_frecuencias_por_nivel`
- `test_reglas_para_nivel_retorna_estructura_completa`

---

### Unit — Services (12 tests)

#### `DashboardServiceTest` (12 tests)
> `tests/Unit/Services/DashboardServiceTest.php`

- `test_admin_stats_average_progress`
- `test_admin_stats_counts_indicators`
- `test_admin_stats_counts_overdue`
- `test_admin_stats_counts_programs`
- `test_admin_stats_zero_when_no_data`
- `test_avance_por_programa`
- `test_operador_stats_counts_pending`
- `test_results_are_cached`
- `test_semaforo_distribution`
- `test_semaforo_distribution_empty`
- `test_team_isolation`
- `test_tendencia_captura_fills_missing_months`

---

### Unit — Tracking (2 tests)

#### `OrphanFileCleanupTest` (2 tests)
> `tests/Unit/Tracking/OrphanFileCleanupTest.php`

- `test_deleting_avance_removes_evidencias_and_directory`
- `test_deleting_evidencia_removes_file_from_storage`

---
