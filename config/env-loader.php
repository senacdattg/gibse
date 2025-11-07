<?php

/**
 * Cargador de variables de entorno desde archivo .env
 * 
 * Esta clase proporciona funcionalidad para cargar y acceder a variables
 * de entorno desde un archivo .env siguiendo principios POO, SOLID y DRY.
 * 
 * @package Config
 * @author Jhon Hader Rodriguez Perdomo
 * @version 2.0.0
 */
class EnvLoader
{
    /**
     * Instancia única de la clase (Singleton)
     * 
     * @var EnvLoader|null
     */
    private static ?EnvLoader $instance = null;

    /**
     * Ruta del archivo .env
     * 
     * @var string
     */
    private string $envFile;

    /**
     * Indica si las variables ya fueron cargadas
     * 
     * @var bool
     */
    private bool $loaded = false;

    /**
     * Constructor privado para implementar patrón Singleton
     * 
     * @param string|null $envFile Ruta personalizada al archivo .env
     */
    private function __construct(?string $envFile = null)
    {
        $this->envFile = $envFile ?? $this->getDefaultEnvPath();
        $this->load();
    }

    /**
     * Obtiene la instancia única de la clase (Singleton)
     * 
     * @param string|null $envFile Ruta personalizada al archivo .env
     * @return EnvLoader
     */
    public static function getInstance(?string $envFile = null): EnvLoader
    {
        if (self::$instance === null) {
            self::$instance = new self($envFile);
        }

        return self::$instance;
    }

    /**
     * Obtiene la ruta por defecto del archivo .env
     * 
     * @return string
     */
    private function getDefaultEnvPath(): string
    {
        return __DIR__ . '/../.env';
    }

    /**
     * Carga las variables de entorno desde el archivo .env
     * 
     * @return void
     */
    private function load(): void
    {
        if ($this->loaded || !$this->envFileExists()) {
            return;
        }

        $lines = $this->readEnvFile();

        foreach ($lines as $line) {
            if ($this->isValidLine($line)) {
                $this->setEnvironmentVariable($line);
            }
        }

        $this->loaded = true;
    }

    /**
     * Verifica si el archivo .env existe
     * 
     * @return bool
     */
    private function envFileExists(): bool
    {
        return file_exists($this->envFile) && is_readable($this->envFile);
    }

    /**
     * Lee el contenido del archivo .env
     * 
     * @return array
     */
    private function readEnvFile(): array
    {
        $content = file($this->envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        return $content !== false ? $content : [];
    }

    /**
     * Valida si una línea del archivo .env es válida
     * 
     * @param string $line Línea a validar
     * @return bool
     */
    private function isValidLine(string $line): bool
    {
        $trimmedLine = trim($line);

        // Ignorar comentarios
        if (str_starts_with($trimmedLine, '#')) {
            return false;
        }

        // Debe contener el signo =
        return str_contains($trimmedLine, '=');
    }

    /**
     * Procesa y establece una variable de entorno
     * 
     * @param string $line Línea del archivo .env
     * @return void
     */
    private function setEnvironmentVariable(string $line): void
    {
        [$key, $value] = $this->parseLine($line);

        if ($this->shouldSetVariable($key)) {
            $this->setVariable($key, $value);
        }
    }

    /**
     * Parsea una línea del archivo .env extrayendo clave y valor
     * 
     * @param string $line Línea a parsear
     * @return array{0: string, 1: string}
     */
    private function parseLine(string $line): array
    {
        [$key, $value] = explode('=', $line, 2);

        $key = trim($key);
        $value = $this->cleanValue(trim($value));

        return [$key, $value];
    }

    /**
     * Limpia el valor removiendo comillas innecesarias
     * 
     * @param string $value Valor a limpiar
     * @return string
     */
    private function cleanValue(string $value): string
    {
        return trim($value, '"\'');
    }

    /**
     * Verifica si se debe establecer la variable de entorno
     * 
     * No sobrescribe variables ya existentes en el sistema
     * 
     * @param string $key Clave de la variable
     * @return bool
     */
    private function shouldSetVariable(string $key): bool
    {
        return !isset($_ENV[$key]) && getenv($key) === false;
    }

    /**
     * Establece una variable de entorno en $_ENV y putenv
     * 
     * @param string $key Clave de la variable
     * @param string $value Valor de la variable
     * @return void
     */
    private function setVariable(string $key, string $value): void
    {
        $_ENV[$key] = $value;
        putenv("$key=$value");
    }

    /**
     * Obtiene una variable de entorno
     * 
     * @param string $key Nombre de la variable
     * @param mixed $default Valor por defecto si no existe
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        // Prioridad 1: $_ENV
        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }

        // Prioridad 2: getenv()
        $value = getenv($key);
        if ($value !== false) {
            return $value;
        }

        // Prioridad 3: Valor por defecto
        return $default;
    }
}

/**
 * Función helper para obtener una variable de entorno (compatibilidad hacia atrás)
 * 
 * @param string $key Nombre de la variable
 * @param mixed $default Valor por defecto si no existe
 * @return mixed
 */
function getEnvVar(string $key, $default = null)
{
    return EnvLoader::getInstance()->get($key, $default);
}

// Cargar variables de entorno automáticamente
EnvLoader::getInstance();
