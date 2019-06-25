<?php
/**
 * A library to connect and submit and verify the electronic documents from Hacienda v4.3 Costa Rica
 * By Sergio Castillo <sergio.cs87@yahoo.com>
 * 
 * ----------------------------------------------------------------------------
 * "THE BEER-WARE LICENSE" (Revision 42):
 * As long as you retain this notice you can do whatever you want with this 
 * stuff. If we meet some day, and you think this stuff is worth it, you can 
 * buy me a beer in return.
 * ----------------------------------------------------------------------------
 */

/**
 * Description of IDP_Hacienda
 *
 * @author sergio
 */

namespace FECR;

abstract class IDP {

    //Environment modes
    const STAGING = 0;
    const PRODUCTION = 1;
    //Debug modes
    const NONE = 0;
    const ERROR = 1;
    const DEBUG = 2;
    const INFO = 3;
    /**
     * Type: Factura Electronica
     */
    const FE = "01";
    /**
     * Type: Nota Debito
     */
    const ND = "02";
    /**
     * Type: Nota Credito
     */
    const NC = "03";
    /**
     * Type: Tiquete Electronico
     */
    const TE = "04";
    /**
     * Type: Confirmacion Aceptacion
     */
    const CA = "05";
    /**
     * Type: Confirmacion Parcial
     */
    const CP = "06";
    /**
     * Type: Confirmacion Rechazo
     */
    const CR = "07";
    /**
     * Type: Factura de Compra
     */
    const FC = "08";
    /**
     * Type: Factura de Exportacion
     */
    const FX = "09";

}

function defaultLogger($msg, $mode) {
    $level = "";
    switch ($mode) {
        case IDP::ERROR: $level = "ERROR";
            break;
        case IDP::DEBUG: $level = "DEBUG";
            break;
        case IDP::INFO: $level = "INFO";
            break;
    }
    echo date("Y-m-d H:i:s") . " $level $msg\n";
}

class APIHacienda {

    private $realm;
    private $debugLevel;
    private $logger;

    /**
     * 
     * @param type $realm
     * @param type $debug
     */
    function __construct($realm = IDP::PRODUCTION, $debug = IDP::NONE) {
        $this->realm = $realm;
        $this->debugLevel = $debug;
        $this->logger = "FECR\defaultLogger";  //Default logger function, any method that receives a message and the mode as parameters and does whatever it wants with it
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $this->log("IDP Hacienda started in mode " . $this->getClientId(), IDP::INFO);
    }

    private function log($msg, $mode = IDP::DEBUG) {
        if ($mode <= $this->debugLevel) {
            call_user_func_array($this->logger, [$msg, $mode]);
        }
    }

    private function getAccessToken() {
        return $_SESSION['idp_access_token'];
    }

    private function setAccessToken($access_token) {
        $_SESSION['idp_access_token'] = $access_token;
    }

    private function getRefreshToken() {
        return $_SESSION['idp_refresh_token'];
    }

    private function setRefreshToken($refresh_token) {
        $_SESSION['idp_refresh_token'] = $refresh_token;
    }

    private function getClientId() {
        if ($this->realm == IDP::STAGING) {
            return "api-stag";
        } else {
            return "api-prod";
        }
    }

    private function getIDPUrl($endpoint) {
        if ($this->realm == IDP::STAGING) {
            return "https://idp.comprobanteselectronicos.go.cr/auth/realms/rut-stag/protocol/openid-connect/" . $endpoint;
        } else {
            return "https://idp.comprobanteselectronicos.go.cr/auth/realms/rut/protocol/openid-connect/" . $endpoint;
        }
    }
    
    private function getReceptionUrl($endpoint) {
        if ($this->realm == IDP::STAGING) {
            return "https://api.comprobanteselectronicos.go.cr/recepcion-sandbox/v1/" . $endpoint;
        } else {
            return "https://api.comprobanteselectronicos.go.cr/recepcion/v1/" . $endpoint;
        }
    }

    private function getUsername() {
        return $_SESSION['idp_username'];
    }

    private function getPassword() {
        return $_SESSION['idp_password'];
    }

    private function makeAPICall($url, $data) {
        $data = http_build_query($data);

        $context_options = [
            "http" => [
                "method" => "POST",
                "header" => "Content-type: application/x-www-form-urlencoded\r\n" .
                "Content-Length: " . strlen($data) . "\r\n",
                "content" => $data
            ]
        ];

        $context = stream_context_create($context_options);
        $response = "";
        try {
            $response = @file_get_contents($url, false, $context);
        } catch (\Exception $ex) {
            $this->log("Error making API call: " . $ex->getMessage(), IDP::ERROR);
            return false;
        }

        return $response;
    }
    
    /**
     * 
     * @param type $url
     * @param type $data
     * @return type
     * @throws Exception
     */
    private function makeTicketCall($url, $data = null) {
        $this->log("Making call to $url", IDP::INFO);
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $method = "GET";
        $header = ['Authorization: bearer ' . $this->getAccessToken()];
        
        if ($data != null) {
            $msg = json_encode($data);
            $method = "POST";
            curl_setopt($curl, CURLOPT_POSTFIELDS, $msg);
            $header[] = 'Content-Type: application/json';
        }
        
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);

        $response = curl_exec($curl);
        if ($response === false) {
            throw new Exception("Error making API call: " . curl_error($curl));
        }
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $bodyText = substr($response, $header_size);
        $bodyJSON = json_decode($bodyText);
        $arrayResp = array(
            "status"   => $status,
            "header"   => substr($response, 0, $header_size),
            "bodyText" => $bodyText,
            "bodyJSON" => $bodyJSON
        );
        curl_close($curl);
        return $arrayResp;
    }

//****** PUBLIC MEMBERS ******

    /**
     * 
     * @param type $username
     * @param type $password
     */
    public function setCredentials($username, $password) {
        $_SESSION['idp_username'] = $username;
        $_SESSION['idp_password'] = $password;
    }

    public function registerLogger($functionName) {
        $this->logger = $functionName;
    }

    /**
     * 
     * @return boolean
     */
    public function requestAccessToken() {
        $data = [
            "client_id" => $this->getClientId(),
            "username" => $this->getUsername(),
            "password" => $this->getPassword(),
            "grant_type" => "password"
        ];

        $response = $this->makeAPICall($this->getIDPUrl("token"), $data);

        if ($response === false) {
            $this->log("Other unknown error getting token.", IDP::ERROR);
            return false;
        }

        $json = json_decode($response);
        if ($json === null) {
            $this->log("Unable to decode json string.", IDP::ERROR);
            return false;
        }

        $this->setAccessToken($json->access_token);
        $this->setRefreshToken($json->refresh_token);
        $this->log("Access token: " . $this->getAccessToken(), IDP::INFO);
        $this->log("Refresh token: " . $this->getRefreshToken(), IDP::INFO);

        return true;
    }

    public function refreshAccessToken() {
        $refreshToken = $this->getRefreshToken();
        if (!$refreshToken) {
            $this->log("Unable to refresh access token: no refresh token available.", IDP::ERROR);
            return false;
        }

        $data = [
            "client_id" => $this->getClientId(),
            "grant_type" => "refresh_token",
            "refresh_token" => $refreshToken
        ];

        $response = $this->makeAPICall($this->getIDPUrl("token"), $data);

        if ($response === false) {
            $this->log("Other unknown error refreshing token.", IDP::ERROR);
            return false;
        }

        $json = json_decode($response);
        if ($json === null) {
            $this->log("Unable to decode json string.", IDP::ERROR);
            return false;
        }

        $this->setAccessToken($json->access_token);
        $this->setRefreshToken($json->refresh_token);
        $this->log("Access token: " . $this->getAccessToken(), IDP::INFO);
        $this->log("Refresh token: " . $this->getRefreshToken(), IDP::INFO);

        return true;
    }

    /**
     * 
     * @return boolean
     */
    public function logout() {
        $refreshToken = $this->getRefreshToken();
        if (!$refreshToken) {
            $this->log("Unable to logout: no refresh token available.", IDP::ERROR);
            return false;
        }

        $data = [
            "client_id" => $this->getClientId(),
            "refresh_token" => $refreshToken
        ];

        $this->makeAPICall($this->getIDPUrl("logout"), $data);

        $this->log("IDP session logged out.", IDP::INFO);

        return true;
    }

    /**
     * 
     * @param type $xmlSigned
     * @param \FECR\FacturaElectronica $data
     * @return type
     */
    public function send($xmlSigned, FacturaElectronica $data) {
        $msg = array(
            'clave' => $data->Clave,
            'fecha' => $data->FechaEmision,
            'emisor' => array(
                'tipoIdentificacion' => $data->Emisor->Identificacion->Tipo,
                'numeroIdentificacion' => $data->Emisor->Identificacion->Numero
            ),
            'receptor' => array(
                'tipoIdentificacion' => $data->Receptor->Identificacion->Tipo,
                'numeroIdentificacion' => $data->Receptor->Identificacion->Numero
            ),
            'comprobanteXml' => base64_encode($xmlSigned)
        );

        return $this->makeTicketCall($this->getReceptionUrl("recepcion"), $msg);
    }

    /**
     * 
     * @param type $xmlSigned
     * @param \FECR\FacturaElectronica $data
     * @param type $consecutivoReceptor
     * @return type
     */
    public function sendMessage($xmlSigned, MensajeReceptor $data, $emisor_id_tipo, $receptor_id_tipo) {
        $msg = array(
            'clave' => $data->Clave,
            'fecha' => $data->FechaEmisionDoc,
            'emisor' => array(
                'tipoIdentificacion' => $emisor_id_tipo,
                'numeroIdentificacion' => $data->NumeroCedulaEmisor
            ),
            'receptor' => array(
                'tipoIdentificacion' => $receptor_id_tipo,
                'numeroIdentificacion' => $data->NumeroCedulaReceptor
            ),
            'consecutivoReceptor' => $data->NumeroConsecutivoReceptor,
            'comprobanteXml' => base64_encode($xmlSigned)
        );

        return $this->makeTicketCall($this->getReceptionUrl("recepcion"), $msg);
    }

    /**
     * 
     * @param type $xmlSigned
     * @param \FECR\FacturaElectronica $data
     * @return type
     */
    public function sendTE($xmlSigned, FacturaElectronica $data) {
        $msg = array(
            'clave' => $data->Clave,
            'fecha' => $data->FechaEmision,
            'emisor' => array(
                'tipoIdentificacion' => $data->Emisor->Identificacion->Tipo,
                'numeroIdentificacion' => $data->Emisor->Identificacion->Numero
            ),
            'comprobanteXml' => base64_encode($xmlSigned)
        );

        return $this->makeTicketCall($this->getReceptionUrl("recepcion"), $msg);
    }
    
    /**
     * Gets Comprobantes headers from Hacienda.
     * If no $clave is specified then it will retrieve the header of all Comprobantes according to the filters selected.
     * @param type $clave
     * @param \FECR\IdentificacionType $emisorIdent
     * @param \FECR\IdentificacionType $receptorIdent
     * @param type $offset
     * @param type $limit
     * @return type
     */
    public function getComprobantes($clave = 0, IdentificacionType $emisorIdent = null, IdentificacionType $receptorIdent = null, $offset = 0, $limit = 0) {
        $endpoint = "comprobantes";
        if ($clave > 0) {
            $endpoint .= "/$clave";
        } else {
            $args = "";
            $args .= $offset > 0 ? "offset=$offset" : "";
            $args .= $limit > 0 ? ($args == "" ? "" : "&") . "limit=$limit" : "";
            $args .= $emisorIdent   != null ? ($args == "" ? "" : "&") . "emisor="   . $emisorIdent->Tipo   . $emisorIdent->Numero   : "";
            $args .= $receptorIdent != null ? ($args == "" ? "" : "&") . "receptor=" . $receptorIdent->Tipo . $receptorIdent->Numero : "";
            if ($args != "") {
                $endpoint .= "?" . $args;
            }
        }

        return $this->makeTicketCall($this->getReceptionUrl($endpoint));
    }
    
    /**
     * 
     * @param type $clave
     * @return type
     */
    public function getComprobanteStatus($clave) {
        $endpoint = "recepcion/$clave";
        return $this->makeTicketCall($this->getReceptionUrl($endpoint));
    }

}
