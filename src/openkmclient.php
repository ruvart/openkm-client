<?php
/**
 * OpenKMClient
 * 
 * Clase para conectar a la api REST de OpenKM
 * 
 * Requiere PHP 8.0 o más
 * 
 * ? ¿Vale la pena acomodar para que se puedan pasar funciones en caso de error o éxito?
 * 
 * @author Ruvart, <contacto@ruvart.com>
 * @version 0.1
*/

namespace Ruvart\OpenKMClient;

class OpenKMClientException extends Exception {}

class OpenKMClient {


    /**
     * Ruta base de mis carpetas
     * 
     * @var String
     * @access private
     */
    private $root = '';


    /**
     * Ruta base de categorías
     * 
     * @var String
     * @access private
     */
    private $categories_root = "/okm:categories/";


    /**
     * Ruta base de mis categorías
     * 
     * @var String
     * @access private
     */
    private $my_categories_root = '';


    /**
     * Respuestas http
     * 
     * @link https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
     * @var Array
     * @access private
     */
    private $http_response = [
        '100' => 'Continue',
        '101' => 'Switching Protocols',
        '102' => 'Processing (WebDAV; RFC 2518)',
        '103' => 'Early Hints (RFC 8297)',

        '200' => 'OK',
        '201' => 'Created',
        '202' => 'Accepted',
        '203' => 'Non-Authoritative Information (since HTTP/1.1)',
        '204' => 'No Content',
        '205' => 'Reset Content',
        '206' => 'Partial Content (RFC 7233)',
        '207' => 'Multi-Status (WebDAV; RFC 4918)',
        '208' => 'Already Reported (WebDAV; RFC 5842)',
        '226' => 'IM Used (RFC 3229)',
        
        '300' => 'Multiple Choices',
        '301' => 'Moved Permanently',
        '302' => 'Found (Previously "Moved temporarily")',
        '303' => 'See Other (since HTTP/1.1)',
        '304' => 'Not Modified (RFC 7232)',
        '305' => 'Use Proxy (since HTTP/1.1)',
        '306' => 'Switch Proxy',
        '307' => 'Temporary Redirect (since HTTP/1.1)',
        '308' => 'Permanent Redirect (RFC 7538)',
        
        '400' => 'Bad Request',
        '401' => 'Unauthorized (RFC 7235)',
        '402' => 'Payment Required',
        '403' => 'Forbidden',
        '404' => 'Not Found',
        '405' => 'Method Not Allowed',
        '406' => 'Not Acceptable',
        '407' => 'Proxy Authentication Required (RFC 7235)',
        '408' => 'Request Timeout',
        '409' => 'Conflict',
        '410' => 'Gone',
        '411' => 'Length Required',
        '412' => 'Precondition Failed (RFC 7232)',
        '413' => 'Payload Too Large (RFC 7231)',
        '414' => 'URI Too Long (RFC 7231)',
        '415' => 'Unsupported Media Type (RFC 7231)',
        '416' => 'Range Not Satisfiable (RFC 7233)',
        '417' => 'Expectation Failed',
        '418' => "I'm a teapot",
        '421' => 'Misdirected Request (RFC 7540)',
        '422' => 'Unprocessable Entity (WebDAV; RFC 4918)',
        '423' => 'Locked (WebDAV; RFC 4918)',
        '424' => 'Failed Dependency (WebDAV; RFC 4918)',
        '425' => 'Too Early (RFC 8470)',
        '426' => 'Upgrade Required',
        '428' => 'Precondition Required (RFC 6585)',
        '429' => 'Too Many Requests (RFC 6585)',
        '431' => 'Request Header Fields Too Large (RFC 6585)',
        '451' => 'Unavailable For Legal Reasons (RFC 7725)',

        '500' => 'Internal Server Error',
        '501' => 'Not Implemented',
        '502' => 'Bad Gateway',
        '503' => 'Service Unavailable',
        '504' => 'Gateway Timeout',
        '505' => 'HTTP Version Not Supported',
        '506' => 'Variant Also Negotiates (RFC 2295)',
        '507' => 'Insufficient Storage (WebDAV; RFC 4918)',
        '508' => 'Loop Detected (WebDAV; RFC 5842)',
        '510' => 'Not Extended (RFC 2774)',
        '511' => 'Network Authentication Required (RFC 6585)',
    ];


    /**
     * Listado de tipos de error
     * 
     * @var Array
     * @access private
     */
    private $error_types = [
        'serviceInvalidException',
        'invalidAcceptedTypeException',
        'requestErrorException',
        'createOrOpenFileException',
        'fileNameNotValidException',
        'localFileNotFoundException',
        'localPathNotFoundException',
        'parameterRetornarInvalidException',

        'PathNotFoundException',
        'ItemExistsException',
        'NullPointerException',
        'DatabaseException',
        'RuntimeException',
        'ArrayIndexOutOfBoundsException',
    ];


    /**
     * El servicio que se ejecutó
     * 
     * @var String
     * @access private
     */
    private $service_executed = '';


    /**
     * El método que se utilizó
     * 
     * @var String
     * @access private
     */
    private $method_used = '';


    /**
     * Código http que respondió
     * 
     * @var String
     * @access private
     */
    private $http_code = '';


    /**
     * Tipo de error
     * 
     * @var String
     * @access private
     */
    private $error_type = '';


    /**
     * Mensaje de error que se generó
     * 
     * @var String
     * @access private
     */
    private $error_message = '';


    public function __construct (
        /**
          * Ruta al servidor OpenKM
          * 
          * @var String
          * @access private
          */
        private String $url_OKM,

        /**
          * Usuario
          * 
          * @var String
          * @access private
          */
        private String $user,

        /**
          * Password
          * 
          * @var String
          * @access private
          */
        private String $password,

        /**
          * Bandera que indica si lo serrores se van a reportar por excepciones o por el return
          *
          * @var Bool
          * @access private
          */
        private Bool $error_through_exception = false
    ) {

        $this->url_OKM .= (($this->url_OKM[strlen($this->url_OKM) - 1] != '/') ? '/' : '') . 'OpenKM/services/rest/';
        $this->root               = "/okm:personal/{$this->user}/";
        $this->my_categories_root = "";                              //"/okm:categories/{$this->user}/";
    }


    //---------------------------  Funciones Base  ---------------------------

        /**
         * Ejecuta una petición a la api Rest
         * 
         * @param String $method
         * @param String $service
         * @param Array $parameters_get
         * @param Mixed $parameters_post
         * @param String $accept
         * @param String $file
         * @param String $download_to
         * @param Array $query_same_name
         */
        public function service(String $service,Array $parameters_get = [],Mixed $parameters_post = false,String $accept = 'json',String $method = 'GET',String $file = '',String $download_to = '',Array $query_same_name = []) {
            //Limpia las variables de respuesta
                $this->setResponse();
            //Limpia las variables de respuesta


            //Valida el servicio
                $method = strtoupper(trim($method));

                if ( empty($service) ) {
                    $this->setResponse($service,$method,'','serviceInvalidException','El servicio no puede estar vacío');

                    return false;
                }

                $api_url = $this->url_OKM . $service;
            //Valida el servicio

            //Procesa y valida accept
                $headers = [];

                $accept = strtolower($accept);

                try {
                    $headers[] = match ( $accept ) {
                        'json'         => 'accept: application/json',
                        'xml'          => 'accept: application/xml',
                        'text'         => 'accept: text/plain',
                        'octet-stream' => 'accept: octet-stream',
                        'pdf'          => 'accept: application/pdf',
                    };
                } catch (\UnhandledMatchError $e) {
                    $this->setResponse($service,$method,'','invalidAcceptedTypeException','El tipo de valor aceptado no es válido');

                    return false;
                }
            //Procesa y valida accept

            //Valida los parámetros GET
                if ( !empty($parameters_get) ) {
                    if ( empty($query_same_name) ) {
                        $api_url .= '?' . http_build_query($parameters_get);
                    }
                    
                    else {
                        $parametros_normal       = [];
                        $parametros_nombre_igual = '';
                        
                        foreach ( $parameters_get as $key_param => $value_param ) {
                            if ( !in_array($key_param,$query_same_name) ) {
                                $parametros_normal[$key_param] = $value_param;
                            }
                            else {
                                $key_param = urlencode($key_param);
                                $parametros_nombre_igual .= "{$key_param}=" . implode("&{$key_param}=", array_map(fn($t_parameter) => urlencode($t_parameter), $value_param) ) . "&";
                            }
                        }
                        
                        $api_url .= "?" . ((!empty($parametros_normal)) ? http_build_query($parametros_normal) . '&' : '') . ((!empty($parametros_nombre_igual)) ? $parametros_nombre_igual : '');
                    }
                }
            //Valida los parámetros GET

            //Valida el archivo
                $with_file = false;
                if ( !empty($file) ) {
                    if ( file_exists($file) && !is_dir($file) ) {
                        //Si me mandan parámetros de post como array
                            if ( !empty($parameters_post) && is_array($parameters_post) ) {
                                $parameters_post['content'] = new CURLFile($file);
                            }
                        //Si me mandan parámetros de post como array

                        //Si no me mandan parámetros de post o es un texto plano
                            else {
                                $parameters_post = [
                                    'content' => new CURLFile($file),
                                ];
                            }
                        //Si no me mandan parámetros de post o es un texto plano

                        $with_file = true;
                    }

                    else {
                        $this->setResponse($service,$method,'','localFileNotFoundException','El archivo no existe');

                        return false;
                    }
                }
            //Valida el archivo

            //Valida descarga del archivo
                $download_to_dir = '';
                $temp_file_name  = '';
                if ( !empty($download_to) ) {
                    $partes_ruta = pathinfo($download_to);

                    //La ruta es un directorio
                        $download_to_last_char = substr($download_to, -1);
                        if ( (file_exists($download_to) && is_dir($download_to)) || ($download_to_last_char == "\\" || $download_to_last_char == "/") || empty($partes_ruta['extension']) ) {
                            $this->setResponse($service,$method,'','fileNameNotValidException','El nombre de archivo no es válido (es un directorio)');

                            return false;
                        }
                    //La ruta es un directorio

                    //Directorio padre
                        if ( !empty($partes_ruta['dirname']) && (!file_exists($partes_ruta['dirname']) || !is_dir($partes_ruta['dirname'])) ) {
                            $this->setResponse($service,$method,'','localPathNotFoundException','El directorio no existe');

                            return false;
                        }

                        if ( strlen($partes_ruta['dirname']) > 1 || (strlen($partes_ruta['dirname']) == 1 && $partes_ruta['dirname'] != "\\" && $partes_ruta['dirname'] != "/") ) {
                            $partes_ruta['dirname'] .= '/';
                        }
                    //Directorio padre

                    //Archivo temporal
                        $temp_file_name = $partes_ruta['dirname'] . $this->generateTempName();
                    //Archivo temporal
                }
            //Valida descarga del archivo

            //Valida los parámetros POST
                $mensaje_post = false;
                if ( !empty($parameters_post) ) {
                    $with_post = true;

                    //Si mandan archivos
                        if ( $with_file ) {
                            $mensaje_post = $parameters_post;

                            $headers[] = 'Content-Type: multipart/form-data';
                        }
                    //Si mandan archivos

                    //Si mandan arreglo de parámetros post
                        elseif ( is_array($parameters_post) ) {
                            $mensaje_post = json_encode($parameters_post);

                            $headers[] = 'Content-Type: application/json';
                        }
                    //Si mandan arreglo de parámetros post

                    //Si mandan texto plano
                        else {
                            $mensaje_post = $parameters_post;

                            $headers[] = 'Content-Type: application/json';
                        }
                    //Si mandan texto plano
                }
                else {
                    $with_post = ($method == 'POST') ? true : false;
                }
            //Valida los parámetros POST
            
            
            //cURL
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $api_url);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FAILONERROR, false);
                curl_setopt($ch, CURLOPT_USERPWD, "{$this->user}:{$this->password}");

                curl_setopt($ch, CURLOPT_POST, $with_post);
                if ( $mensaje_post ) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $mensaje_post);
                }

                if ( $method != 'GET' && $method != 'POST' ) {
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
                }

                if ( !empty($download_to) ) {
                    set_time_limit(0);

                    $fp = @fopen($temp_file_name, 'w+');

                    if ( !$fp ) {
                        $this->setResponse($service,$method,'','createOrOpenFileException','No se pudo abrir/crear el archivo destino');

                        return false;
                    }

                    curl_setopt($ch, CURLOPT_TIMEOUT, 600);
                    curl_setopt($ch, CURLOPT_FILE, $fp); 
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                }
            //cURL


            //Si ocurre un error desde la petición
                if ( ($response = curl_exec($ch)) === FALSE ) {
                    $errno         = curl_errno($ch);
                    $error_message = curl_strerror($errno);
                    $error_msg     = str_ireplace('The requested URL returned error:','',curl_error($ch));
                    $http_code     = curl_getinfo($ch,CURLINFO_HTTP_CODE);
                    $http_message  = ( isset($this->http_response[$http_code]) ) ? $this->http_response[$http_code] : '';

                    curl_close($ch);

                    $error_msg = "(1) Ocurrió un error al tratar de sincronizar: \n[" . $error_msg . "] ({$errno}) =&gt; {$error_message}";

                    //Si descarga archivo
                        if ( !empty($download_to) ) {
                            fclose($fp);
                            $response_in_file = file_get_contents($temp_file_name);

                            $error_msg .= "\n " . $response_in_file;

                            @unlink($temp_file_name);
                        }
                    //Si descarga archivo

                    $error_type = 'requestErrorException';
                    $this->setResponse($service,$method,$http_code,$error_type,$error_msg);

                    $return = [
                        'service'      => $service,
                        'http_code'    => $http_code,
                        'http_message' => $http_message,
                        'result'       => false,
                        'msg'          => $error_msg,
                        'error_type'   => $error_type,
                    ];
                }
            //Si ocurre un error desde la petición

            //Si No ocurre un error en la petición
                else {
                    $http_code    = curl_getinfo($ch,CURLINFO_HTTP_CODE);
                    $http_message = ( isset($this->http_response[$http_code]) ) ? $this->http_response[$http_code] : '';
                    $error_msg    = '';
                    $error_type   = '';
                    $result       = false;

                    //Si mandan mensaje de error
                        if ( $http_code >= 300 || $http_code < 200 ) {
                            $errno         = curl_errno($ch);
                            $error_message = curl_strerror($errno);
                            $error_msg     = str_ireplace('The requested URL returned error:','',curl_error($ch));

                            curl_close($ch);

                            if ( $errno ) {
                                $error_msg = "(2) Ocurrió un error al tratar de sincronizar: \n[" . $error_msg . "] ({$errno}) =&gt; {$error_message}";
                            }
                            
                            else {
                                $error_msg = "(3) Ocurrió un error al tratar de sincronizar:";
                            }

                            //Si no descarga archivo
                                if ( empty($download_to) ) {
                                    $error_msg .= "\n " . $response;
                                }
                            //Si no descarga archivo

                            //Si descarga archivo
                                else {
                                    fclose($fp);
                                    $response_in_file = file_get_contents($temp_file_name);
                
                                    $error_msg .= "\n " . $response_in_file;
                
                                    @unlink($temp_file_name);

                                    $response = $response_in_file;
                                }
                            //Si descarga archivo

                            //Error type
                                $error_type = 'requestErrorException';
                                foreach ( $this->error_types as $t_type ) {
                                    if ( stripos($response,$t_type) !== false ) {
                                        $error_type = $t_type;
                                        break;
                                    }
                                }
                            //Error type
                        }
                    //Si mandan mensaje de error
                    
                    //Si todo está correcto
                        else {
                            curl_close($ch);

                            //Si se descargó un archivo
                                if ( !empty($download_to) ) {
                                    fclose($fp);

                                    if ( !@rename($temp_file_name,$download_to) ) {
                                        $this->setResponse($service,$method,'','createOrOpenFileException','No se pudo abrir/crear el archivo destino');

                                        return false;
                                    }

                                    $result = 'OK';
                                }
                            //Si se descargó un archivo

                            //Si no se descargó un archivo
                                else {
                                    if ( $accept == 'json' && !empty($response) ) {
                                        $result = json_decode($response,true);
                                        
                                        if ( empty($result) && trim($response) != '{}' ) {
                                            $result = $response;
                                        }
                                    }
                                    else {
                                        $result = $response;
                                    }
                                }
                            //Si no se descargó un archivo
                        }
                    //Si todo está correcto


                    $this->setResponse($service,$method,$http_code,$error_type,$error_msg);

                    $return = [
                        'service'      => $service,
                        'http_code'    => $http_code,
                        'http_message' => $http_message,
                        'result'       => $result,
                        'msg'          => $error_msg,
                        'error_type'   => $error_type,
                    ];
                }
            //Si No ocurre un error en la petición

            return $return;
        }


        /**
         * Asigna las variables de respuesta
         * 
         * @param String $service
         * @param String $method
         * @param String $httpCode
         * @param String $errorType
         * @param String $errorMessage
         */
        private function setResponse(String $service = '',String $method = '',String $httpCode = '',String $errorType = '',String $errorMessage = '') {
            $this->service_executed = $service;
            $this->method_used = $method;
            $this->http_code        = $httpCode;
            $this->error_type       = $errorType;
            $this->error_message    = $errorMessage;

            if ( $this->error_through_exception && !empty($this->error_type) ) {
                $exception_msg  = "{$this->error_type}: {$this->error_message}\n";
                $exception_msg .= "- Service: {$this->service_executed}\n";
                $exception_msg .= "- Method: {$this->method_used}\n";
                $exception_msg .= "- Endpoint: {$this->url_OKM}{$this->service_executed}\n";
                $exception_msg .= "- Http code: {$this->http_code}\n";
                $exception_msg .= "- Http message: " . ((!empty($this->http_code) && isset($this->http_response[$this->http_code])) ? $this->http_response[$this->http_code] : '') . "\n";
                throw new OpenKMClientException($exception_msg);
            }
        }


        /**
         * Regresa las respuestas y mensajes de error
         * 
         * @param String $variable
         * @return Mixed
         */
        public function getResponse(String $variable = '') {
            return match ( strtolower($variable) ) {
                'service'       => $this->service_executed,
                'method'       => $this->method_used,
                'endpoint'      => $this->url_OKM . $this->service_executed,
                'http_code'     => $this->http_code,
                'http_message'  => ( !empty($this->http_code) && isset($this->http_response[$this->http_code]) ) ? $this->http_response[$this->http_code] : '',
                'error_type'    => $this->error_type,
                'error_message' => $this->error_message,
                ''              => [
                    'service'       => $this->service_executed,
                    'method'       => $this->method_used,
                    'endpoint'      => $this->url_OKM . $this->service_executed,
                    'http_code'     => $this->http_code,
                    'http_message'  => ( !empty($this->http_code) && isset($this->http_response[$this->http_code]) ) ? $this->http_response[$this->http_code] : '',
                    'error_type'    => $this->error_type,
                    'error_message' => $this->error_message,
                ],
                default => false,
            };
        }


        /**
         * Regresa la ruta raíz del usuario
         * 
         * @return String
         */
        public function getRoot() {
            return $this->root;
        }


        /**
         * Regresa la ruta raíz de mis categorías
         * 
         * @return String
         */
        public function getMyCategoriesRoot() {
            return $this->categories_root . $this->my_categories_root;
        }


        /**
         * Genera un nombre de archivo temporal
         * 
         * @return String
         */
        private function generateTempName() {
            $length           = 20;
            $characters       = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $charactersLength = strlen($characters);
            $randomString     = '';
            for ($i = 0; $i < $length; $i++) {
                $randomString .= $characters[rand(0, $charactersLength - 1)];
            }

            $name = date('Ymd-His-') . $randomString . '.tmp';
            return $name;
        }

    //---------------------------  Funciones Base  ---------------------------




    //---------------------------  Funciones de usuarios  ---------------------------

        /**
         * Hace el login de un usuario.
         * 
         * Sirve para crear las carpetas que aun no se hayan creado
         * 
         * @return Bool
         */
        public function authLogin() {
            $result = $this->service('auth/login');

            if ( !empty($result['error_type']) ) {
                return false;
            }

            return true;
        }


        /**
         * Regresa los roles de mi usuario
         * 
         * @return Array
         */
        public function authGetMyRoles() {
            $result = $this->service('auth/getRolesByUser/' . $this->user);

            if ( empty($result['error_type']) && !empty($result['result']) && isset($result['result']['role']) ) {
                return $result['result']['role'];
            }

            return false;
        }


        /**
         * Regresa el nombre de mi usuario
         * 
         * @return String
         */
        public function authGetMyName() {
            $result = $this->service('auth/getName/' . $this->user);

            if ( empty($result['error_type']) && !empty($result['result']) ) {
                return $result['result'];
            }

            return false;
        }

    //---------------------------  Funciones de usuarios  ---------------------------


    //---------------------------  Funciones del repositorio  ---------------------------

        /**
         * Obtiene la carpeta raiz
         * 
         * @param Bool $solo_ruta
         * @return Array | String
         */
        public function repositoryGetRootFolder(Bool $solo_ruta = false) {
            $result = $this->service('repository/getRootFolder');

            if ( !empty($result['error_type']) ) {
                return false;
            }

            elseif ( !$solo_ruta ) {
                return $result['result'];
            }

            elseif ( !empty($result['result']) && isset($result['result']['path']) ) {
                return $result['result']['path'];
            }

            else {
                return false;
            }
        }


        /**
         * Obtiene la carpeta personal base
         * 
         * @param Bool $solo_ruta
         * @return Array | String
         */
        public function repositoryGetPersonalFolderBase(Bool $solo_ruta = false) {
            $result = $this->service('repository/getPersonalFolderBase');

            if ( !empty($result['error_type']) ) {
                return false;
            }

            elseif ( !$solo_ruta ) {
                return $result['result'];
            }

            elseif ( !empty($result['result']) && isset($result['result']['path']) ) {
                return $result['result']['path'];
            }

            else {
                return false;
            }
        }


        /**
         * Obtiene la carpeta de correos
         * 
         * @param Bool $solo_ruta
         * @return Array | String
         */
        public function repositoryGetMailFolder(Bool $solo_ruta = false) {
            $result = $this->service('repository/getMailFolder');

            if ( !empty($result['error_type']) ) {
                return false;
            }

            elseif ( !$solo_ruta ) {
                return $result['result'];
            }

            elseif ( !empty($result['result']) && isset($result['result']['path']) ) {
                return $result['result']['path'];
            }

            else {
                return false;
            }
        }


        /**
         * Obtiene la carpeta de papelera
         * 
         * @param Bool $solo_ruta
         * @return Array | String
         */
        public function repositoryGetTrashFolder(Bool $solo_ruta = false) {
            $result = $this->service('repository/getTrashFolder');

            if ( !empty($result['error_type']) ) {
                return false;
            }

            elseif ( !$solo_ruta ) {
                return $result['result'];
            }

            elseif ( !empty($result['result']) && isset($result['result']['path']) ) {
                return $result['result']['path'];
            }

            else {
                return false;
            }
        }


        /**
         * Obtiene la carpeta personal
         * 
         * @param Bool $solo_ruta
         * @return Array | String
         */
        public function repositoryGetPersonalFolder(Bool $solo_ruta = false) {
            $result = $this->service('repository/getPersonalFolder');

            if ( !empty($result['error_type']) ) {
                return false;
            }

            elseif ( !$solo_ruta ) {
                return $result['result'];
            }

            elseif ( !empty($result['result']) && isset($result['result']['path']) ) {
                return $result['result']['path'];
            }

            else {
                return false;
            }
        }


        /**
         * Limpia la papelera
         * 
         * @return Bool
         */
        public function repositoryPurgeTrash() {
            $result = $this->service('repository/purgeTrash',[],'','json','DELETE');

            if ( !empty($result['error_type']) ) {
                return false;
            }

            return true;
        }


        /**
         * Regresa la ruta a un nodo por su uuid
         * 
         * @param String $uuid
         * @return String
         */
        public function repositoryGetNodePath(String $uuid) {
            $result = $this->service('repository/getNodePath/' . $uuid);

            if ( empty($result['error_type']) && !empty($result['result']) ) {
                return $result['result'];
            }

            return false;
        }


        /**
         * Regresa el uuid de una ruta
         * 
         * @param String $ruta
         * @return String
         */
        public function repositoryGetNodeUuid(String $ruta) {
            $result = $this->service('repository/getNodeUuid',['nodePath' => $ruta]);

            if ( empty($result['error_type']) && !empty($result['result']) ) {
                return $result['result'];
            }

            return false;
        }

    //---------------------------  Funciones del repositorio  ---------------------------


    //---------------------------  Funciones de carpetas  ---------------------------
        
        /**
         * Comprueba si una carpeta es válida
         * 
         * @param String $fldId Puede ser ruta o uuid
         * @return Bool
         */
        public function folderIsValid(String $fldId) {
            $result = $this->service('folder/isValid',['fldId' => $fldId],'','text');

            if ( empty($result['error_type']) && !empty($result['result']) ) {
                return (strtolower($result['result']) == 'true') ? true : false;
            }

            return false;
        }


        /**
         * Obtiene la información de una carpeta
         * 
         * @param String $fldId Puede ser ruta o uuid
         * @return Array
         */
        public function folderGetContentInfo(String $fldId) {
            $result = $this->service('folder/getContentInfo',['fldId' => $fldId]);

            if ( empty($result['error_type']) && !empty($result['result']) ) {
                return $result['result'];
            }

            return false;
        }


        /**
         * Obtiene las propiedades de una carpeta
         * 
         * @param String $fldId Puede ser ruta o uuid
         * @return Array
         */
        public function folderGetProperties(String $fldId) {
            $result = $this->service('folder/getProperties',['fldId' => $fldId]);

            if ( empty($result['error_type']) && !empty($result['result']) ) {
                return $result['result'];
            }

            return false;
        }


        /**
         * Obtiene la ruta a una carpeta por su uuid
         * 
         * @param String $uuid
         * @return String
         */
        public function folderGetPath(String $uuid) {
            $result = $this->service('folder/getPath/' . $uuid);

            if ( empty($result['error_type']) && !empty($result['result']) ) {
                return $result['result'];
            }

            return false;
        }


        /**
         * Obtiene los hijos de una carpeta (solo regresa carpetas)
         * 
         * @param String $fldId Puede ser ruta o uuid
         * @param String $retornar Puede name, path o object
         * @return Array
         */
        public function folderGetChildren(String $fldId, String $retornar = 'name') {
            //Valida lo que se quiere retornar
                if ( $retornar != 'name' && $retornar != 'path' && $retornar != 'object' ) {
                    $this->setResponse('folder/getChildren','GET','','parameterRetornarInvalidException','El tipo de valor retornado no es válido');
                    return false;
                }
            //Valida lo que se quiere retornar

            $result = $this->service('folder/getChildren',['fldId' => $fldId]);

            if ( empty($result['error_type']) && isset($result['result']) ) {
                $salida = [];

                if ( !empty($result['result']['folder']) ) {
                    //Un solo hijo
                        reset($result['result']['folder']);
                        if ( !is_int(key($result['result']['folder'])) ) {
                            $salida[] = match ( $retornar ) {
                                'name'   => basename($result['result']['folder']['path']) . '/',
                                'path'   => $result['result']['folder']['path'] . '/',
                                'object' => $result['result']['folder'],
                            };
                        }
                    //Un solo hijo

                    //Más de un hijo
                        else {
                            $salida = match ( $retornar ) {
                                'name'   => array_map(fn($t_hijo) => basename($t_hijo['path']) . '/', $result['result']['folder']),
                                'path'   => array_map(fn($t_hijo) => $t_hijo['path'] . '/', $result['result']['folder']),
                                'object' => $result['result']['folder'],
                            };
                        }
                    //Más de un hijo
                }

                return $salida;
            }

            return false;
        }


        /**
         * Crea una carpeta
         * 
         * @param String $name Debe incluir la ruta
         * @param String $retornar Puede ser bool, uuid, path o object
         * @return Mixed
         */
        public function folderCreateSimple(String $name,String $retornar = 'bool') {
            //Valida lo que se quiere retornar
                if ( $retornar != 'bool' && $retornar != 'uuid' && $retornar != 'path' && $retornar != 'object' ) {
                    $this->setResponse('folder/createSimple','POST','','parameterRetornarInvalidException','El tipo de valor retornado no es válido');
                    return false;
                }
            //Valida lo que se quiere retornar

            $result = $this->service('folder/createSimple',[],$name);

            if ( empty($result['error_type']) && !empty($result['result']) ) {
                if ( $retornar == 'bool' ) {
                    return true;
                }
                
                elseif ( $retornar == 'object' ) {
                    return $result['result'];
                }

                elseif ( $retornar == 'uuid' && isset($result['result']['uuid']) ) {
                    return $result['result']['uuid'];
                }

                elseif ( $retornar == 'path' && isset($result['result']['path']) ) {
                    return $result['result']['path'];
                }
            }

            return false;
        }


        /**
         * Renombra una carpeta
         * 
         * @param String $fldId Puede ser ruta o uuid
         * @param String $newName Solo es el nombre nuevo, sin la ruta
         * @return Bool
         */
        public function folderRename(String $fldId,String $newName) {
            $result = $this->service('folder/rename',['fldId' => $fldId,'newName' => $newName],'','json','PUT');

            if ( !empty($result['error_type']) ) {
                return false;
            }

            return true;
        }


        /**
         * Copia una carpeta a otro lugar
         * 
         * @param String $fldId Puede ser ruta o uuid
         * @param String $dstId Carpeta destino Puede ser ruta o uuid
         * @return Bool
         */
        public function folderCopy(String $fldId,String $dstId) {
            $result = $this->service('folder/copy',['fldId' => $fldId,'dstId' => $dstId],'','json','PUT');

            if ( !empty($result['error_type']) ) {
                return false;
            }

            return true;
        }


        /**
         * Mueve una carpeta a un nuevo lugar
         * 
         * @param String $fldId Puede ser ruta o uuid
         * @param String $dstId Carpeta destino Puede ser ruta o uuid
         * @return Bool
         */
        public function folderMove(String $fldId,String $dstId) {
            $result = $this->service('folder/move',['fldId' => $fldId,'dstId' => $dstId],'','json','PUT');

            if ( !empty($result['error_type']) ) {
                return false;
            }

            return true;
        }


        /**
         * Elimina una carpeta
         * 
         * @param String $fldId Puede ser ruta o uuid
         * @return Bool
         */
        public function folderDelete(String $fldId) {
            $result = $this->service('folder/delete',['fldId' => $fldId],'','json','DELETE');

            if ( !empty($result['error_type']) ) {
                return false;
            }

            return true;
        }


        /**
         * Elimina de forma permanente una carpeta
         * 
         * @param String $fldId Puede ser ruta o uuid
         * @return Bool
         */
        public function folderPurge(String $fldId) {
            $result = $this->service('folder/purge',['fldId' => $fldId],'','json','PUT');

            if ( !empty($result['error_type']) ) {
                return false;
            }

            return true;
        }

    //---------------------------  Funciones de carpetas  ---------------------------


    //---------------------------  Funciones de documentos  ---------------------------

        /**
         * Comprueba si un documento es válido
         * 
         * @param String $docId Puede ser ruta o uuid
         * @return Bool
         */
        public function documentIsValid(String $docId) {
            $result = $this->service('document/isValid',['docId' => $docId],'','text');

            if ( empty($result['error_type']) && !empty($result['result']) ) {
                return (strtolower($result['result']) == 'true') ? true : false;
            }

            return false;
        }


        /**
         * Obtiene la información de un documento
         * 
         * @param String $docId Puede ser ruta o uuid
         * @return Array
         */
        public function documentGetProperties(String $docId) {
            $result = $this->service('document/getProperties',['docId' => $docId]);

            if ( empty($result['error_type']) && !empty($result['result']) ) {
                return $result['result'];
            }

            return false;
        }


        /**
         * Obtiene la ruta a un documento por su uuid
         * 
         * @param String $uuid
         * @return String
         */
        public function documentGetPath(String $uuid) {
            $result = $this->service('document/getPath/' . $uuid);

            if ( empty($result['error_type']) && !empty($result['result']) ) {
                return $result['result'];
            }

            return false;
        }


        /**
         * Descarga un documento
         * 
         * @param String $docId Puede ser ruta o uuid
         * @param String $name Nombre (incluyendo la ruta) con el que se va a guardar. Si se omite va a tomar el mismo nombre del documento que descargue
         * @return Bool
         */
        public function documentGetContent(String $docId,String $name = '') {
            //Prepara el nombre
                if ( empty($name) ) {
                    if ( strpos($docId,'/') !== false || strpos($docId,"\\") !== false ) {
                        $name = basename($docId);
                    }
                    else {
                        $result = $this->documentGetPath($docId);
                        if ( $result === false ) {
                            return false;
                        }

                        $name = basename($result);
                    }
                }
            //Prepara el nombre

            $result = $this->service('document/getContent',['docId' => $docId],'','octet-stream','','',$name);

            if ( !empty($result['error_type']) ) {
                return false;
            }

            return true;
        }


        /**
         * Obtiene los hijos de un documento (solo regresa documentos)
         * 
         * @param String $fldId Puede ser ruta o uuid
         * @param String $retornar Puede name, path o object
         * @return Array
         */
        public function documentGetChildren(String $fldId, String $retornar = 'name') {
            //Valida lo que se quiere retornar
                if ( $retornar != 'name' && $retornar != 'path' && $retornar != 'object' ) {
                    $this->setResponse('document/getChildren','GET','','parameterRetornarInvalidException','El tipo de valor retornado no es válido');
                    return false;
                }
            //Valida lo que se quiere retornar
            
            $result = $this->service('document/getChildren',['fldId' => $fldId]);
            
            if ( empty($result['error_type']) && isset($result['result']) ) {
                return $this->processesDocumentChildren($result['result'],$retornar);
            }

            return false;
        }


        /**
         * Crea un documento de forma simple
         * 
         * @param String $docPath Nombre y ruta del documento con el que se guardará en el servidor
         * @param String $name Nombre y ruta del documento en la máquina local (de donde se va a subir)
         * @return Bool
         */
        public function documentCreateSimple(String $docPath,String $name) {
            $result = $this->service('document/createSimple',[],['docPath' => $docPath],'json','',$name);

            if ( !empty($result['error_type']) ) {
                return false;
            }

            return true;
        }


        /**
         * Renombra un documento
         * 
         * @param String $docId Puede ser ruta o uuid
         * @param String $newName Solo es el nombre nuevo, sin la ruta
         * @return Bool
         */
        public function documentRename(String $docId,String $newName) {
            $result = $this->service('document/rename',['docId' => $docId,'newName' => $newName],'','json','PUT');

            if ( !empty($result['error_type']) ) {
                return false;
            }

            return true;
        }


        /**
         * Copia un documento a otro lugar sin copiar la información extra (solo el documento "puro" y la información de seguridad / acceso)
         * 
         * @param String $docId Puede ser ruta o uuid
         * @param String $dstId Carpeta destino Puede ser ruta o uuid
         * @return Bool
         */
        public function documentCopy(String $docId,String $dstId) {
            $result = $this->service('document/copy',['docId' => $docId,'dstId' => $dstId],'','json','PUT');

            if ( !empty($result['error_type']) ) {
                return false;
            }

            return true;
        }


        /**
         * Copia un documento a otro lugar y también copia la información extra asociada (categorías, palabras clave, grupos de propiedades, notas y wiki)
         * 
         * $extAttr es un array(
         * 'categories'     => Bool,
         * 'keywords'       => Bool,
         * 'propertyGroups' => Bool,
         * 'notes'          => Bool,
         * 'wiki'           => Bool,
         * )
         * 
         * @param String $docId Puede ser ruta o uuid
         * @param String $dstId Carpeta destino Puede ser ruta o uuid
         * @param Array $extAttr Los atributos a ser copiados junto con el documento
         * @param String $name Nuevo nombre que va a tener el documento. Si se deja vacío tomará el mismo nombre que ya tiene
         * @return Bool
         */
        public function documentExtendedCopy(String $docId,String $dstId,Array $extAttr,String $name = '') {
            //Prepara los parámetros
                $p_query = [
                    'docId'          => $docId,
                    'dstId'          => $dstId,
                    'categories'     => (!empty($extAttr) && !empty($extAttr['categories'])) ? true : false,
                    'keywords'       => (!empty($extAttr) && !empty($extAttr['keywords'])) ? true : false,
                    'propertyGroups' => (!empty($extAttr) && !empty($extAttr['propertyGroups'])) ? true : false,
                    'notes'          => (!empty($extAttr) && !empty($extAttr['notes'])) ? true : false,
                    'wiki'           => (!empty($extAttr) && !empty($extAttr['wiki'])) ? true : false,
                ];

                if ( !empty($name) ) {
                    $p_query['name'] = $name;
                }
            //Prepara los parámetros

            $result = $this->service('document/extendedCopy',$p_query,'','json','PUT');

            if ( !empty($result['error_type']) ) {
                return false;
            }

            return true;
        }


        /**
         * Mueve un documento a un nuevo lugar
         * 
         * @param String $docId Puede ser ruta o uuid
         * @param String $dstId Carpeta destino Puede ser ruta o uuid
         * @return Bool
         */
        public function documentMove(String $docId,String $dstId) {
            $result = $this->service('document/move',['docId' => $docId,'dstId' => $dstId],'','json','PUT');

            if ( !empty($result['error_type']) ) {
                return false;
            }

            return true;
        }


        /**
         * Elimina un documento
         * 
         * @param String $docId Puede ser ruta o uuid
         * @return Bool
         */
        public function documentDelete(String $docId) {
            $result = $this->service('document/delete',['docId' => $docId],'','json','DELETE');

            if ( !empty($result['error_type']) ) {
                return false;
            }

            return true;
        }


        /**
         * Elimina de forma permanente un documento
         * 
         * @param String $docId Puede ser ruta o uuid
         * @return Bool
         */
        public function documentPurge(String $docId) {
            $result = $this->service('document/purge',['docId' => $docId],'','json','PUT');

            if ( !empty($result['error_type']) ) {
                return false;
            }

            return true;
        }



        //---------------------------  Funciones de versiones de documentos  ---------------------------

            /**
             * Regresa el tamaño del histórico
             * 
             * @param String $docId Puede ser ruta o uuid
             * @return String
             */
            public function documentGetVersionHistorySize(String $docId) {
                $result = $this->service('document/getVersionHistorySize',['docId' => $docId],'','text');

                if ( empty($result['error_type']) && !empty($result['result']) ) {
                    return $result['result'];
                }
    
                return false;
            }


            /**
             * Regresa el listado de versiones
             * 
             * @param String $docId Puede ser ruta o uuid
             * @return Array
             */
            public function documentGetVersionHistory(String $docId) {
                $result = $this->service('document/getVersionHistory',['docId' => $docId]);

                if ( empty($result['error_type']) && !empty($result['result']) ) {
                    $salida = [];

                    if ( !empty($result['result']['version']) ) {
                        //Un solo hijo
                            reset($result['result']['version']);
                            if ( !is_int(key($result['result']['version'])) ) {
                                $salida[] = $result['result']['version'];
                            }
                        //Un solo hijo

                        //Más de un hijo
                            else {
                                $salida = $result['result']['version'];
                            }
                        //Más de un hijo
                    }

                    return $salida;
                }

                return false;
            }


            /**
             * Descarga un documento de una versión en específico
             * 
             * @param String $docId Puede ser ruta o uuid
             * @param String $versionId El id de la versión
             * @param String $name Nombre (incluyendo la ruta) con el que se va a guardar. Si se omite va a tomar el mismo nombre del documento que descargue
             * @return Bool
             */
            public function documentGetContentByVersion(String $docId,String $versionId,String $name = '') {
                //Prepara el nombre
                    if ( empty($name) ) {
                        if ( strpos($docId,'/') !== false || strpos($docId,"\\") !== false ) {
                            $name = basename($docId);
                        }
                        else {
                            $result = $this->documentGetPath($docId);
                            if ( $result === false ) {
                                return false;
                            }

                            $name = basename($result);
                        }
                    }
                //Prepara el nombre

                $result = $this->service('document/getContentByVersion',['docId' => $docId, 'versionId' => $versionId],'','octet-stream','','',$name);

                if ( !empty($result['error_type']) ) {
                    return false;
                }

                return true;
            }


            /**
             * Restaura a una versión en específico
             * 
             * @param String $docId Puede ser ruta o uuid
             * @param String $versionId El id de la versión
             * @return Bool
             */
            public function documentRestoreVersion(String $docId,String $versionId) {
                $result = $this->service('document/restoreVersion',['docId' => $docId, 'versionId' => $versionId],'','json','PUT');

                if ( !empty($result['error_type']) ) {
                    return false;
                }

                return true;
            }


            /**
             * Borra el historial de versiones
             * 
             * @param String $docId Puede ser ruta o uuid
             * @return Bool
             */
            public function documentPurgeVersionHistory(String $docId) {
                $result = $this->service('document/purgeVersionHistory',['docId' => $docId],'','json','PUT');

                if ( !empty($result['error_type']) ) {
                    return false;
                }

                return true;
            }


            /**
             * Indica si un documento está en edición
             * 
             * @param String $docId Puede ser ruta o uuid
             * @return Bool
             */
            public function documentIsCheckedOut(String $docId) {
                $result = $this->service('document/isCheckedOut',['docId' => $docId],'','text');

                if ( empty($result['error_type']) && !empty($result['result']) ) {
                    return (strtolower($result['result']) == 'true') ? true : false;
                }
    
                return false;
            }

        
            /**
             * Marca un documento para su edición
             * 
             * @param String $docId Puede ser ruta o uuid
             * @return Bool
             */
            public function documentCheckout(String $docId) {
                $result = $this->service('document/checkout',['docId' => $docId]);

                if ( !empty($result['error_type']) ) {
                    return false;
                }

                return true;
            }


            /**
             * Cancela una edición de un documento
             * 
             * @param String $docId Puede ser ruta o uuid
             * @return Bool
             */
            public function documentCancelCheckout(String $docId) {
                $result = $this->service('document/cancelCheckout',['docId' => $docId],'','json','PUT');

                if ( !empty($result['error_type']) ) {
                    return false;
                }

                return true;
            }


            /**
             * Forza la cancelación de una edición de un documento
             * Solo puede ejecutarlo un administrador
             * 
             * @param String $docId Puede ser ruta o uuid
             * @return Bool
             */
            public function documentForceCancelCheckout(String $docId) {
                $result = $this->service('document/forceCancelCheckout',['docId' => $docId],'','json','PUT');

                if ( !empty($result['error_type']) ) {
                    return false;
                }

                return true;
            }


            /**
             * Sube una nueva versión de un documento
             * 
             * @param String $docId Puede ser ruta o uuid
             * @param String $name Nombre y ruta del documento en la máquina local (de donde se va a subir)
             * @param String $comment Algún comentario adicional
             * @return Bool
             */
            public function documentCheckin(String $docId,String $name,String $comment = '') {
                $result = $this->service('document/checkin',[],['docId' => $docId, 'comment' => $comment],'json','',$name);

                if ( !empty($result['error_type']) ) {
                    return false;
                }

                return true;
            }

        //---------------------------  Funciones de versiones de documentos  ---------------------------



        //---------------------------  Funciones de bloqueo de documentos  ---------------------------

            /**
             * Revisa si está bloqueado un documento
             * 
             * @param String $docId Puede ser ruta o uuid
             * @return Bool
             */
            public function documentIsLocked(String $docId) {
                $result = $this->service('document/isLocked',['docId' => $docId],'','text');

                if ( empty($result['error_type']) && !empty($result['result']) ) {
                    return (strtolower($result['result']) == 'true') ? true : false;
                }
    
                return false;
            }


            /**
             * Regresa la información del bloqueo de un documento
             * 
             * @param String $docId Puede ser ruta o uuid
             * @return Array
             */
            public function documentGetLockInfo(String $docId) {
                $result = $this->service('document/getLockInfo',['docId' => $docId]);

                if ( empty($result['error_type']) && !empty($result['result']) ) {
                    return $result['result'];
                }

                return false;
            }


            /**
             * Bloquea un documento
             * 
             * @param String $docId Puede ser ruta o uuid
             * @return Array
             */
            public function documentLock(String $docId) {
                $result = $this->service('document/lock',['docId' => $docId],'','json','PUT');

                if ( empty($result['error_type']) && !empty($result['result']) ) {
                    return $result['result'];
                }

                return false;
            }


            /**
             * Desbloquea un documento
             * 
             * @param String $docId Puede ser ruta o uuid
             * @return Bool
             */
            public function documentUnlock(String $docId) {
                $result = $this->service('document/unlock',['docId' => $docId],'','json','PUT');

                if ( !empty($result['error_type']) ) {
                    return false;
                }

                return true;
            }


            /**
             * Forza el desbloqueo de un documento
             * Solo puede ejecutarlo un administrador
             * 
             * @param String $docId Puede ser ruta o uuid
             * @return Bool
             */
            public function documentForceUnlock(String $docId) {
                $result = $this->service('document/forceUnlock',['docId' => $docId],'','json','PUT');

                if ( !empty($result['error_type']) ) {
                    return false;
                }

                return true;
            }

        //---------------------------  Funciones de bloqueo de documentos  ---------------------------

        


        /**
         * Acomoda la salida de los resultados de búsqueda
         * 
         * @param Array $resultado
         * @param String $retornar Puede ser path, node o full
         * @return Array
         */
        private function processesDocumentChildren(Array $resultado,String $retornar) {
            //Valida lo que se quiere retornar
                if ( $retornar != 'name' && $retornar != 'path' && $retornar != 'object' ) {
                    $this->setResponse('folder/getChildren','GET','','parameterRetornarInvalidException','El tipo de valor retornado no es válido');
                    return false;
                }
            //Valida lo que se quiere retornar

            $salida = [];
            
            if ( !empty($resultado['document']) ) {
                //Un solo hijo
                    reset($resultado['document']);
                    if ( !is_int(key($resultado['document'])) ) {
                        $salida[] = match ( $retornar ) {
                            'name'   => basename($resultado['document']['path']),
                            'path'   => $resultado['document']['path'],
                            'object' => $resultado['document'],
                        };
                    }
                //Un solo hijo

                //Más de un hijo
                    else {
                        $salida = match ( $retornar ) {
                            'name'   => array_map(fn($t_hijo) => basename($t_hijo['path']), $resultado['document']),
                            'path'   => array_map(fn($t_hijo) => $t_hijo['path'], $result['result']['document']),
                            'object' => $resultado['document'],
                        };
                    }
                //Más de un hijo
            }

            return $salida;
        }

    //---------------------------  Funciones de documentos  ---------------------------


    //---------------------------  Funciones de conversión  ---------------------------

        /**
         * Convierte un documento a PDF
         * 
         * @param String $documentoOrigen El documento a convertir que se va a subir
         * @param String $documentoDestino El nombre del documento cuando se descargue. Si se omite se va a tomar el mismo nombre del que se suba, pero con la extención .pdf
         * @return Bool
         */
        public function conversionDoc2pdf(String $documentoOrigen,String $documentoDestino = '') {
            //Prepara el nombre
                if ( empty($name) ) {
                    if ( strpos($docId,'/') !== false || strpos($docId,"\\") !== false ) {
                        $name = basename($docId);
                    }
                    else {
                        $result = $this->documentGetPath($docId);
                        if ( $result === false ) {
                            return false;
                        }

                        $name = basename($result);
                    }
                }
            //Prepara el nombre
        }

    //---------------------------  Funciones de conversión  ---------------------------


    //---------------------------  Funciones de marcadores  ---------------------------

        /**
         * Obtiene todos los marcadores del usuario
         * 
         * @return Array
         */
        public function bookmarkGetAll() {
            $result = $this->service('bookmark/getAll');

            if ( empty($result['error_type']) && !empty($result['result']) ) {
                $salida = [];

                if ( !empty($result['result']['bookmark']) ) {
                    //Un solo hijo
                        reset($result['result']['bookmark']);
                        if ( !is_int(key($result['result']['bookmark'])) ) {
                            $salida[] = $result['result']['bookmark'];
                        }
                    //Un solo hijo

                    //Más de un hijo
                        else {
                            $salida = $result['result']['bookmark'];
                        }
                    //Más de un hijo
                }

                return $salida;
            }

            return false;
        }


        /**
         * Obtiene la información de un marcador
         * 
         * @param Int $bookmarkId
         * @return Array
         */
        public function bookmarkGet(Int $bookmarkId) {
            $result = $this->service('bookmark/get',['bookmarkId' => $bookmarkId]);

            if ( empty($result['error_type']) && !empty($result['result']) ) {
                return $result['result'];
            }

            return false;
        }


        /**
         * Crea un marcador
         * 
         * @param String $nodeId
         * @param String $body
         * @return Array
         */
        public function bookmarkCreate(String $nodeId,String $body) {
            $result = $this->service('bookmark/create',['nodeId' => $nodeId],$body);

            if ( empty($result['error_type']) && !empty($result['result']) ) {
                return $result['result'];
            }

            return false;
        }


        /**
         * Renombra un marcador
         * 
         * @param Int $bookmarkId
         * @param String $body
         * @return Array
         */
        public function bookmarkRename(Int $bookmarkId,String $body) {
            $result = $this->service('bookmark/rename',['bookmarkId' => $bookmarkId],$body,'json','PUT');

            if ( empty($result['error_type']) && !empty($result['result']) ) {
                return $result['result'];
            }

            return false;
        }


        /**
         * Elimina un marcador
         * 
         * @param Int $bookmarkId
         * @return Bool
         */
        public function bookmarkDelete(Int $bookmarkId) {
            $result = $this->service('bookmark/delete',['bookmarkId' => $bookmarkId],'','json','DELETE');

            if ( empty($result['error_type']) ) {
                return true;
            }

            return false;
        }

    //---------------------------  Funciones de marcadores  ---------------------------


    //---------------------------  Funciones de notas  ---------------------------

        /**
         * Lista las notas de un nodo
         * 
         * @param String $nodeId
         * @return Array
         */
        public function noteList(String $nodeId) {
            $result = $this->service('note/list',['nodeId' => $nodeId]);

            if ( empty($result['error_type']) && !empty($result['result']) ) {
                $salida = [];

                if ( !empty($result['result']['note']) ) {
                    //Un solo hijo
                        reset($result['result']['note']);
                        if ( !is_int(key($result['result']['note'])) ) {
                            $salida[] = $result['result']['note'];
                        }
                    //Un solo hijo

                    //Más de un hijo
                        else {
                            $salida = $result['result']['note'];
                        }
                    //Más de un hijo
                }

                return $salida;
            }

            return false;
        }


        /**
         * Regresa una nota por su id
         * 
         * @param String $noteId
         * @return Array
         */
        public function noteGet(String $noteId) {
            $result = $this->service('note/get',['noteId' => $noteId]);

            if ( empty($result['error_type']) && !empty($result['result']) ) {
                return $result['result'];
            }

            return false;
        }


        /**
         * Crea una nota
         * 
         * @param String $nodeId
         * @param String $body
         * @return Array
         */
        public function noteAdd(String $nodeId,String $body) {
            $result = $this->service('note/add',['nodeId' => $nodeId],$body);

            if ( empty($result['error_type']) && !empty($result['result']) ) {
                return $result['result'];
            }

            return false;
        }


        /**
         * Actualiza la nota
         * 
         * @param String $noteId
         * @param String $body
         * @return Bool
         */
        public function noteSet(String $noteId,String $body) {
            $result = $this->service('note/set',['noteId' => $noteId],$body,'json','PUT');

            if ( empty($result['error_type']) ) {
                return true;
            }

            return false;
        }


        /**
         * Elimina una nota
         * 
         * @param String $noteId
         * @return Bool
         */
        public function noteDelete(String $noteId) {
            $result = $this->service('note/delete',['noteId' => $noteId],$body,'json','DELETE');

            if ( empty($result['error_type']) ) {
                return true;
            }

            return false;
        }

    //---------------------------  Funciones de notas  ---------------------------


    //---------------------------  Funciones de Categorías  ---------------------------
        
        /**
         * Valida si existe una categoría
         * 
         * @param String $name Debe incluir la ruta
         * @return Bool
         */
        public function categoryIsValid(String $name) {
            return $this->folderIsValid($this->getMyCategoriesRoot() . $name);
        }


        /**
         * Regresa las subcategorías
         * 
         * @param String $name Debe incluir la ruta
         * @param String $retornar Puede name, path o object
         * @return Array
         */
        public function categoryGetChildren(String $name, String $retornar = 'name') {
            return $this->folderGetChildren($this->getMyCategoriesRoot() . $name, $retornar);
        }


        /**
         * Crea una categoría
         * 
         * @param String $name Debe incluir la ruta
         * @param String $retornar Puede ser bool, uuid, path o object
         * @return Mixed
         */
        public function categoryCreate(String $name,String $retornar = 'bool') {
            return $this->folderCreateSimple($this->getMyCategoriesRoot() . $name, $retornar);
        }


        /**
         * Renombra una categoría
         * 
         * @param String $name Debe incluir la ruta
         * @param String $newName Solo es el nombre nuevo, sin la ruta
         * @return Bool
         */
        public function categoryRename(String $name,String $newName) {
            return $this->folderRename($this->getMyCategoriesRoot() . $name, $newName);
        }


        /**
         * Copia una categoría
         * 
         * @param String $name Debe incluir la ruta
         * @param String $dstId Categoría de destino
         * @return Bool
         */
        public function categoryCopy(String $name,String $dstId) {
            return $this->folderCopy($this->getMyCategoriesRoot() . $name, $this->getMyCategoriesRoot() . $dstId);
        }


        /**
         * Mueve de lugar una categoría
         * 
         * @param String $name Debe incluir la ruta
         * @param String $dstId Categoría de destino
         * @return Bool
         */
        public function categoryMove(String $name,String $dstId) {
            return $this->folderMove($this->getMyCategoriesRoot() . $name, $this->getMyCategoriesRoot() . $dstId);
        }


        /**
         * Elimina una categoría
         * 
         * @param String $name Debe incluir la ruta
         * @return Bool
         */
        public function categoryDelete(String $name) {
            return $this->folderDelete($this->getMyCategoriesRoot() . $name);
        }


        /**
         * Elimina de forma permanente una categoría
         * 
         * @param String $name Debe incluir la ruta
         * @return Bool
         */
        public function categoryPurge(String $name) {
            return $this->folderPurge($this->getMyCategoriesRoot() . $name);
        }

    //---------------------------  Funciones de Categorías  ---------------------------


    //---------------------------  Funciones de Propiedades (Palabras claves y categorías)  ---------------------------

        /**
         * Agrega una palabra clave
         * 
         * @param String $nodeId
         * @param String $keyword
         * @return Bool
         */
        public function propertyAddKeyword(String $nodeId,String $keyword) {
            $result = $this->service('property/addKeyword',['nodeId' => $nodeId,'keyword' => $keyword],'1','json','POST');

            if ( empty($result['error_type']) ) {
                return true;
            }

            return false;
        }


        /**
         * Elimina una palabra clave
         * 
         * @param String $nodeId
         * @param String $keyword
         * @return Bool
         */
        public function propertyRemoveKeyword(String $nodeId,String $keyword) {
            $result = $this->service('property/removeKeyword',['nodeId' => $nodeId,'keyword' => $keyword],'','json','DELETE');

            if ( empty($result['error_type']) ) {
                return true;
            }

            return false;
        }


        /**
         * Agrega una categoría
         * 
         * @param String $nodeId
         * @param String $catId
         * @return Bool
         */
        public function propertyAddCategory(String $nodeId,String $catId) {
            $result = $this->service('property/addCategory',['nodeId' => $nodeId,'catId' => $this->getMyCategoriesRoot() . $catId],'1','json','POST');

            if ( empty($result['error_type']) ) {
                return true;
            }

            return false;
        }


        /**
         * Elimina una categoría
         * 
         * @param String $nodeId
         * @param String $catId
         * @return Bool
         */
        public function propertyRemoveCategory(String $nodeId,String $catId) {
            $result = $this->service('property/removeCategory',['nodeId' => $nodeId,'catId' => $this->getMyCategoriesRoot() . $catId],'','json','DELETE');

            if ( empty($result['error_type']) ) {
                return true;
            }

            return false;
        }

    //---------------------------  Funciones de Propiedades (Palabras claves y categorías)  ---------------------------


    //---------------------------  Funciones de Búsquedas  ---------------------------

        /**
         * Busca por el nombre
         * 
         * @param String $name
         * @param String $retornar Puede ser path, node o full
         * @return Array
         */
        public function searchFindByName(String $name,String $retornar = 'path') {
            //Valida lo que se quiere retornar
                if ( $retornar != 'path' && $retornar != 'node' && $retornar != 'full' ) {
                    $this->setResponse('search/findByName','GET','','parameterRetornarInvalidException','El tipo de valor retornado no es válido');
                    return false;
                }
            //Valida lo que se quiere retornar

            $result = $this->service('search/findByName',['name' => $name]);

            if ( empty($result['error_type']) && isset($result['result']) ) {
                return $this->processesSearchResult($result['result'],$retornar);
            }

            return false;
        }


        /**
         * Busca por palabras clave
         * 
         * @param Array $keyword
         * @param String $retornar Puede ser path, node o full
         * @return Array
         */
        public function searchFindByKeywords(Array $keyword,String $retornar = 'path') {
            //Valida lo que se quiere retornar
                if ( $retornar != 'path' && $retornar != 'node' && $retornar != 'full' ) {
                    $this->setResponse('search/findByKeywords','GET','','parameterRetornarInvalidException','El tipo de valor retornado no es válido');
                    return false;
                }
            //Valida lo que se quiere retornar

            $result = $this->service('search/findByKeywords',['keyword' => $keyword],'','json','','','',['keyword']);

            if ( empty($result['error_type']) && isset($result['result']) ) {
                return $this->processesSearchResult($result['result'],$retornar);
            }

            return false;
        }


        /**
         * Busca por un texto en el contenido de los documentos
         * 
         * @param String $content
         * @param String $retornar Puede ser path, node o full
         * @return Array
         */
        public function searchFindByContent(String $content,String $retornar = 'path') {
            //Valida lo que se quiere retornar
                if ( $retornar != 'path' && $retornar != 'node' && $retornar != 'full' ) {
                    $this->setResponse('search/findByContent','GET','','parameterRetornarInvalidException','El tipo de valor retornado no es válido');
                    return false;
                }
            //Valida lo que se quiere retornar

            $result = $this->service('search/findByContent',['content' => $content]);

            if ( empty($result['error_type']) && isset($result['result']) ) {
                return $this->processesSearchResult($result['result'],$retornar);
            }

            return false;
        }


        /**
         * Busca los documentos por la categoría
         * 
         * @param String $categoryId Es el id, no el nombre
         * @param String $retornar Puede ser path, node o full
         * @return Array
         */
        public function searchGetCategorizedDocuments(String $categoryId,String $retornar = 'path') {
            //Valida lo que se quiere retornar
                if ( $retornar != 'path' && $retornar != 'node' && $retornar != 'full' ) {
                    $this->setResponse("search/getCategorizedDocuments/{$categoryId}",'GET','','parameterRetornarInvalidException','El tipo de valor retornado no es válido');
                    return false;
                }
            //Valida lo que se quiere retornar

            $result = $this->service("search/getCategorizedDocuments/{$categoryId}");
            
            if ( empty($result['error_type']) && isset($result['result']) ) {
                return $this->processesDocumentChildren($result['result'],$retornar);
            }

            return false;
        }


        /**
         * Busca los documentos por un filtro más personalizado
         * 
         * $params es un array(
         *  'content'          => String,
         *  'name'             => String,
         *  'domain'           => Int,
         *  'keyword'          => Array(String),
         *  'category'         => Array(String),
         *  'property'         => Array(String),
         *  'author'           => String,
         *  'mimeType'         => String,
         *  'lastModifiedFrom' => String,
         *  'lastModifiedTo'   => String,
         *  'mailSubject'      => String,
         *  'mailFrom'         => String,
         *  'mailTo'           => String,
         *  'path'             => String,
         * )
         * 
         * @param Array $params Array
         * @param String $retornar Puede ser path, node o full
         * @return Array
         */
        public function searchFind(Array $params,String $retornar = 'path') {
            //Valida lo que se quiere retornar
                if ( $retornar != 'path' && $retornar != 'node' && $retornar != 'full' ) {
                    $this->setResponse('search/find','GET','','parameterRetornarInvalidException','El tipo de valor retornado no es válido');
                    return false;
                }
            //Valida lo que se quiere retornar

            $params = array_filter($params, fn($param) => !empty($param));
            $result = $this->service('search/find',$params,'','json','','','',['keyword','category','property']);

            if ( empty($result['error_type']) && isset($result['result']) ) {
                return $this->processesSearchResult($result['result'],$retornar);
            }

            return false;
        }


        /**
         * Busca los documentos por un filtro más personalizado
         * 
         * $params es un array(
         *  'content'          => String,
         *  'name'             => String,
         *  'domain'           => Int,
         *  'keyword'          => Array(String),
         *  'category'         => Array(String),
         *  'property'         => Array(String),
         *  'author'           => String,
         *  'mimeType'         => String,
         *  'lastModifiedFrom' => String,
         *  'lastModifiedTo'   => String,
         *  'mailSubject'      => String,
         *  'mailFrom'         => String,
         *  'mailTo'           => String,
         *  'path'             => String,
         * )
         * 
         * @param Array $params Array
         * @param Int $offset resultado inicial comenzando desde 0. No es la página, es el registro (row, node, document)
         * @param Int $limit Cantidad de resultados
         * @param String $retornar Puede ser path, node o full
         * @return Array
         */
        public function searchFindPaginated(Array $params,Int $offset = 0,Int $limit = 10,String $retornar = 'path') {
            //Valida lo que se quiere retornar
                if ( $retornar != 'path' && $retornar != 'node' && $retornar != 'full' ) {
                    $this->setResponse('search/findPaginated','GET','','parameterRetornarInvalidException','El tipo de valor retornado no es válido');
                    return false;
                }
            //Valida lo que se quiere retornar
            
            $params = array_filter($params, fn($param) => !empty($param));

            $params['offset'] = $offset;
            $params['limit']  = $limit;

            $result = $this->service('search/findPaginated',$params,'','json','','','',['keyword','category','property']);

            if ( empty($result['error_type']) && isset($result['result']) ) {
                return [
                    'total'   => (!empty($result['result']['total'])) ? $result['result']['total'] : 0,
                    'results' => $this->processesSearchResult($result['result'],$retornar,'results'),
                ];
            }

            return false;
        }




        /**
         * Acomoda la salida de los resultados de búsqueda
         * 
         * @param Array $resultado
         * @param String $retornar Puede ser path, node o full
         * @param String $result_name
         * @return Array
         */
        private function processesSearchResult(Array $resultado,String $retornar,String $result_name = 'queryResult') {
            $salida = [];

            if ( !empty($resultado[$result_name]) ) {
                //Un solo hijo
                    reset($resultado[$result_name]);
                    if ( !is_int(key($resultado[$result_name])) ) {
                        $salida[] = match ( $retornar ) {
                            'path' => $resultado[$result_name]['node']['path'],
                            'node' => $resultado[$result_name]['node'],
                            'full' => $resultado[$result_name],
                        };
                    }
                //Un solo hijo

                //Más de un hijo
                    else {
                        $salida = match ( $retornar ) {
                            'path' => array_map(fn($t_hijo) => $t_hijo['node']['path'], $resultado[$result_name]),
                            'node' => array_map(fn($t_hijo) => $t_hijo['node'], $resultado[$result_name]),
                            'full' => $resultado[$result_name],
                        };
                    }
                //Más de un hijo
            }

            return $salida;
        }

    //---------------------------  Funciones de Búsquedas  ---------------------------


}