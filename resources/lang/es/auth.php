<?php

return [

    // Estas venían de fábrica
    'failed'   => 'Estas credenciales no coinciden con nuestros registros.',
    'password' => 'La contraseña ingresada es incorrecta.',
    'throttle' => 'Demasiados intentos de acceso. Por favor intenta nuevamente en :seconds segundos.',

    // Redirecciones en controlador AuthController.php
    'token_invalid'            => 'El enlace no es válido o ha expirado.',
    'email_not_verified'       => 'La cuenta no ha sido verificada correctamente.',
    'token_mismatch'           => 'El enlace de activación es inválido o ha expirado.',
    'email_token_mismatch'     => 'Los datos proporcionados no coinciden.',
    'password_already_defined' => 'La cuenta ya tiene contraseña definida.',
    'password_set_success'     => 'Contraseña definida correctamente. Ahora puede iniciar sesión.',
    'set_password_instruction' => 'Para activar su cuenta, cree una contraseña.',
    'activation_error'         => 'Ocurrió un error al activar tu cuenta. Intenta nuevamente o contacta al administrador.',

    // Redirecciones en controlador ForgotPasswordController.php    
    'email_not_found'        => 'El correo electrónico ingresado no existe en la plataforma.',
    'account_not_verified'   => "Tu cuenta aún no ha sido activada.\nPor favor revisa tu correo y sigue el enlace de activación.",
    'reset_link_sent'        => '¡Le hemos enviado por correo electrónico su enlace de restablecimiento de contraseña!',
    'reset_link_failed'      => 'No se pudo enviar el enlace de recuperación. Intente nuevamente más tarde.',
    'reset_link_error'       => 'Ocurrió un error al intentar enviar el enlace de recuperación. Intente nuevamente más tarde.',

    // Redirecciones en controlador LoginController.php
    'account_inactive'   => 'La cuenta está desactivada. Contacte al administrador del sistema.',

    // Redirecciones en controlador ResetPasswordController.php
    'reset_link_invalid' => 'La contraseña ya fue reestablecida o el enlace expiró.',
    'reset_form_error'   => 'Ocurrió un error al intentar abrir el formulario de restablecimiento de contraseña.',

    // Redirecciones en controlador VerificationController.php
    'verification_link_invalid' => "El enlace de activación ha expirado o no es válido.\nPor favor solicita uno nuevo.",
    'account_already_verified'  => 'Tu cuenta ya ha sido activada.',
    'verification_error'        => 'Ocurrió un error al intentar verificar tu correo electrónico. Intenta nuevamente.',

    // Redirecciones en controlador UserController.php
    'user_created_and_activation_sent' => 'Se ha creado el usuario correctamente y se ha enviado un enlace de activación al correo electrónico: :email',
    'user_creation_error'              => 'Ocurrió un error al crear el usuario. Por favor, inténtalo nuevamente o contacta al administrador.',
    'user_updated_successfully'        => 'Se ha modificado el usuario correctamente.',
    'user_update_error'                => 'Ocurrió un error al actualizar los datos del usuario.',
    'user_status_changed'              => 'El usuario ha sido correctamente :status.',
    'user_status_change_error'         => 'No se pudo cambiar el estado del usuario. Intenta nuevamente.',
    'email_already_verified'           => 'Este usuario ya verificó su correo.',
    'welcome_successfully_sent'        => 'Correo de bienvenida reenviado correctamente.',

    // Redirecciones en controlador ProfileController.php
    'profile_updated_successfully'  => 'El perfil ha sido actualizado correctamente',
    'profile_update_error'          => 'No fue posible actualizar tu perfil. Intenta nuevamente.',
    'password_updated_successfully' => 'Se ha actualizado la contraseña correctamente.',
    'password_update_error'         => 'Error al actualizar la contraseña. Intenta nuevamente.',
    'unauthorized_profile_edit'     => 'No tienes permiso para editar el perfil de otro usuario.',

    // Redirecciones en controlador CamiónController.php
    'truck_created_successfully'    => 'Se ha creado el camión correctamente.',
    'truck_creation_error'          => 'No se pudo crear el camión. Intenta nuevamente.',
    'truck_updated_successfully'    => 'Se ha modificado el camión correctamente.',
    'truck_update_error'            => 'No se pudo actualizar el camión. Intenta nuevamente.',
    'truck_status_changed'          => 'El camión ha sido correctamente :status.',
    'truck_status_change_error'     => 'No se pudo cambiar el estado del camión.',

    // Redirecciones en controlador ConductorController.php
    'driver_created_successfully'   => 'Se ha creado el conductor correctamente.',
    'driver_creation_error'         => 'Ocurrió un error al intentar crear el conductor.',
    'driver_updated_successfully'   => 'Se ha modificado el conductor correctamente.',
    'driver_update_error'           => 'Ocurrió un error al intentar actualizar la información del conductor.',
    'driver_status_changed'         => 'El conductor ha sido correctamente :status.',
    'driver_status_change_error'    => 'No se pudo cambiar el estado del conductor.',

    // Redirecciones en controlador EmpresaController.php
    'company_created_successfully'            => 'Se ha creado la empresa correctamente.',
    'company_creation_error'                  => 'No se pudo crear la empresa. Por favor, revisa los datos e inténtalo nuevamente.',
    'company_updated_successfully'            => 'Se ha modificado la empresa correctamente.',
    'company_update_error'                    => 'No se pudo actualizar la empresa.',
    'company_status_changed'                  => 'La empresa ha sido correctamente :status.',
    'company_status_change_error'             => 'No se pudo cambiar el estado de la empresa.',
    'company_plants_linked_successfully'      => 'Plantas asociadas correctamente.',
    'company_plants_link_error'               => 'Ocurrió un error al vincular las plantas a la empresa.',
    'only_producer_companies_can_link_plants' => 'Solo las empresas productoras pueden gestionar vinculación con plantas.',

    // Redirecciones en controlador SucursalController.php
    'branch_created_successfully'            => 'Se ha creado la sucursal correctamente.',
    'branch_creation_error'                  => 'No se pudo crear la sucursal. Por favor, revisa los datos e inténtalo nuevamente.',
    'branch_updated_successfully'            => 'Se ha modificado la sucursal correctamente.',
    'branch_update_error'                    => 'No se pudo actualizar la sucursal. Intenta nuevamente.',
    'branch_status_changed'                  => 'La sucursal ha sido correctamente :status.',
    'branch_status_change_error'             => 'No se pudo cambiar el estado de la sucursal.',
    'branch_producers_linked_successfully'   => 'Productoras asociadas correctamente.',
    'branch_producers_link_error'            => 'Ocurrió un error al vincular las productoras a la sucursal.',
    'only_plant_branches_can_link_producers' => 'Solo las sucursales tipo Planta de Proceso pueden gestionar vinculación con empresas productoras.',

    // Subject de correo en notificador CustomResetPassword.php
    'password_reset_subject' => 'Restablecer contraseña en LaPortada',

    // Subject de correo en notificador CustomVerifyWelcomeEmail.php
    'welcome_email_subject' => '¡Bienvenid@ a EcoRuta el Sistema de Planificación de Retiro de Subproductos de La Portada!',

    // Redirecciones en middleware CheckRoleAccess.php
    'access_denied' => 'No tienes permisos para acceder a esta sección del sistema.',

];
