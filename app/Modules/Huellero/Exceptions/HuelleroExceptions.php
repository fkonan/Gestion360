<?php

namespace App\Modules\Huellero\Exceptions;

use Exception;

class HuelleroException extends Exception
{
    //
}

class DeviceNotConnectedException extends HuelleroException
{
    protected $message = 'No hay dispositivos de huella conectados';
}

class FingerprintCaptureException extends HuelleroException
{
    protected $message = 'Error al capturar la huella dactilar';
}

class FingerprintVerificationException extends HuelleroException
{
    protected $message = 'Error en la verificación de la huella';
}

class FingerprintNotRecognizedException extends HuelleroException
{
    protected $message = 'Huella dactilar no reconocida';
}

class PersonNotFoundException extends HuelleroException
{
    protected $message = 'Persona no encontrada en el sistema';
}

class FingerprintAlreadyExistsException extends HuelleroException
{
    protected $message = 'La huella ya está registrada para este dedo';
}

class InvalidFingerprintDataException extends HuelleroException
{
    protected $message = 'Los datos de la huella son inválidos';
}
