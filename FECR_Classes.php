<?php
/**
 * The XML Classes needed to construct XML for Hacienda v4.3 Costa Rica
 * By Sergio Castillo <sergio.cs87@yahoo.com>
 * 
 * ----------------------------------------------------------------------------
 * "THE BEER-WARE LICENSE" (Revision 42):
 * As long as you retain this notice you can do whatever you want with this 
 * stuff. If we meet some day, and you think this stuff is worth it, you can 
 * buy me a beer in return.
 * ----------------------------------------------------------------------------
 */

namespace FECR;

class CodigoType {
    public $Tipo;
    public $Codigo;
    //Tipo values
    const VENDEDOR = "01";
    const COMPRADOR = "02";
    const INDUSTRIA = "03";
    const INTERNO = "04";
    const OTROS = "99";
    
    public function __construct($Codigo, $Tipo = CodigoType::VENDEDOR) {
        $this->Codigo = $Codigo;
        $this->Tipo = $Tipo;
    }
    
    public static function getTypes() {
        $reflectionClass = new \ReflectionClass(__CLASS__);
        return $reflectionClass->getConstants();
    }
}

class IdentificacionType {
    public $Tipo;
    public $Numero;
    //Tipo values
    const CED_FISICA = "01";
    const CED_JURIDICA = "02";
    const DIMEX = "03";
    const NITE = "04";
    
    public function __construct($Numero, $Tipo = IdentificacionType::CED_FISICA) {
        $this->Numero = $Numero;
        $this->Tipo = $Tipo;
    }
    
    public static function getTypes() {
        $reflectionClass = new \ReflectionClass(__CLASS__);
        return $reflectionClass->getConstants();
    }
    
    public function process() {
        switch($this->Tipo) {
            case IdentificacionType::CED_FISICA:
                $rx = "/\d{9,9}/";
                if (preg_match($rx, $this->Numero) == 0) {
                    throw new \Exception("Identificacion is in the wrong format \d{9,9} for ced. fisica.");
                }
                break;
            case IdentificacionType::CED_JURIDICA: case IdentificacionType::NITE:
                $rx = "/\d{10,10}/";
                if (preg_match($rx, $this->Numero) == 0) {
                    throw new \Exception("Identificacion is in the wrong format \d{10,10} for ced. juridica and nite.");
                }
                break;
            case IdentificacionType::DIMEX:
                $rx = "/\d{11,12}/";
                if (preg_match($rx, $this->Numero) == 0) {
                    throw new \Exception("Identificacion is in the wrong format \d{9,9} for dimex.");
                }
                break;
        }
    }
}

class UbicacionType {
    //Below are all number codes in string format.
    public $Provincia;
    public $Canton;
    public $Distrito;
    public $Barrio = null;  //optional
    /**
     *
     * @var string max length = 160
     */
    public $OtrasSenas = "";
    
    /**
     * Uses a public API to retrieve the list of provinces, cantons and districts from Costa Rica.
     * Credit to https://programando.paginasweb.cr/2016/04/29/lista-de-provincias-cantones-y-distritos-de-costa-rica-en-formato-json/
     * Returns null if some error occurred.
     * @return stdClass
     */
    public static function getProvincias() {
        return json_decode(htmlentities(@file_get_contents("https://ubicaciones.paginasweb.cr/provincias.json"), ENT_NOQUOTES, "UTF-8"));
    }
    
    /**
     * Uses a public API to retrieve the list of provinces, cantons and districts from Costa Rica.
     * Credit to https://programando.paginasweb.cr/2016/04/29/lista-de-provincias-cantones-y-distritos-de-costa-rica-en-formato-json/
     * Returns null if some error occurred.
     * @param int $provNum
     * @return stdClass
     */
    public static function getCantones($provNum) {
        return json_decode(htmlentities(@file_get_contents("https://ubicaciones.paginasweb.cr/provincia/$provNum/cantones.json"), ENT_NOQUOTES, "UTF-8"));
    }
    
    /**
     * Uses a public API to retrieve the list of provinces, cantons and districts from Costa Rica.
     * Credit to https://programando.paginasweb.cr/2016/04/29/lista-de-provincias-cantones-y-distritos-de-costa-rica-en-formato-json/
     * Returns null if some error occurred.
     * @param int $provNum
     * @param int $cantonNum
     * @return stdClass
     */
    public static function getDistritos($provNum, $cantonNum) {
        return json_decode(htmlentities(@file_get_contents("https://ubicaciones.paginasweb.cr/provincia/$provNum/canton/$cantonNum/distritos.json"), ENT_NOQUOTES, "UTF-8"));
    }
    
    public function process() {
        $rxProv = "/^\d$/";
        $rx = "/^\d\d$/";
        if (preg_match($rxProv, $this->Provincia) == 0 or preg_match($rx, $this->Canton) == 0 or preg_match($rx, $this->Distrito) == 0) {
            throw new \Exception("Provincia {\d}, Canton or Distrito {\\d\\d} are in wrong format.");
        }
        if ($this->OtrasSenas == "") {
            throw new \Exception("OtrasSenas cannot be empty");
        }
        $this->OtrasSenas = substr($this->OtrasSenas, 0, 160);
        if (empty($this->Barrio)) {
            unset($this->Barrio);
        } elseif (preg_match($rx, $this->Barrio) == 0) {
            throw new \Exception("Barrio is in wrong format {\\d\\d}");
        }
    }
}

class TelefonoType {
    public $CodigoPais;
    public $NumTelefono;
    
    public function __construct($CodigoPais, $NumTelefono) {
        $this->CodigoPais = $CodigoPais;
        $this->NumTelefono = $NumTelefono;
        $this->NumTelefono = preg_replace("/[^\d]/", "", $this->NumTelefono);
        if ($CodigoPais === "506") {
            if (preg_match("/^\d{8,8}$/", $this->NumTelefono) == 0) {
                throw new \Exception("Formato para numeros de Telefono de Costa Rica debe ser 8 digitos.");
            }
        }
    }
}

class EmisorType {
    public $Nombre = "";
    /**
     * @var IdentificacionType
     */
    public $Identificacion = null;
    public $NombreComercial = "";
    /**
     * @var UbicacionType
     */
    public $Ubicacion = null;
    /**
     * @var TelefonoType
     */
    public $Telefono = null;
    /**
     * @var TelefonoType
     */
    public $Fax = null;
    public $CorreoElectronico = "";
    
    public function process() {
        if ($this->Identificacion == null or $this->Nombre == "" or $this->CorreoElectronico == "") {
            throw new \Exception("Emisor: Nombre, Identificacion or CorreoElectronico missing.");
        }
        if (empty($this->NombreComercial)) {
            unset($this->NombreComercial);
        }
        if (empty($this->Ubicacion)) {
            throw new \Exception("Emisor: Ubicacion missing.");
        }
        if (empty($this->Telefono)) {
            unset($this->Telefono);
        } else {
            if (!is_a($this->Telefono, "FECR\TelefonoType")) {
                throw new \Exception("Telefono: is not of type TelefonoType.");
            }
        }
        if (empty($this->Fax)) {
            unset($this->Fax);
        } else {
            if (!is_a($this->Fax, "FECR\TelefonoType")) {
                throw new \Exception("Fax: is not of type TelefonoType.");
            }
        }
        $this->Ubicacion->process();
        $this->Identificacion->process();
    }
}

class ReceptorType {
    public $Nombre = "";
    /**
     * @var IdentificacionType
     */
    public $Identificacion = null;
    public $IdentificacionExtranjero = null;
    public $NombreComercial = null;
    /**
     * @var UbicacionType
     */
    public $Ubicacion = null;
    public $OtrasSenasExtranjero = null;
    /**
     * @var TelefonoType
     */
    public $Telefono = null;
    /**
     * @var TelefonoType
     */
    public $Fax = null;
    public $CorreoElectronico = null;
    
    public function process() {
        if ($this->Nombre == "") {
            throw new \Exception("Receptor: Nombre missing");
        }
        if (empty($this->Identificacion)) {
            unset($this->Identificacion);
        } else {
            $this->Identificacion->process();
        }
        if (empty($this->IdentificacionExtranjero)) {
            unset($this->IdentificacionExtranjero);
        } else {
            if (sizeof($this->IdentificacionExtranjero > 20)) {
                $this->IdentificacionExtranjero = substr($this->IdentificacionExtranjero, 0, 20);
            }
        }
        if (empty($this->NombreComercial)) {
            unset($this->NombreComercial);
        }
        if (empty($this->Ubicacion)) {
            unset($this->Ubicacion);
        } else {
            $this->Ubicacion->process();   
        }
        if (empty($this->OtrasSenasExtranjero)) {
            unset($this->OtrasSenasExtranjero);
        }
        if (empty($this->Telefono)) {
            unset($this->Telefono);
        } else {
            if (!is_a($this->Telefono, "FECR\TelefonoType")) {
                throw new \Exception("Telefono: is not of type TelefonoType.");
            }
        }
        if (empty($this->Fax)) {
            unset($this->Fax);
        } else {
            if (!is_a($this->Fax, "FECR\TelefonoType")) {
                throw new \Exception("Fax: is not of type TelefonoType.");
            }
        }
        if (empty($this->CorreoElectronico)) {
            unset($this->CorreoElectronico);
        }        
    }
}

class ExoneracionType {
    /**
     * One of the constant values of the class.
     * @var type 
     */
    public $TipoDocumento;
    /**
     * 
     * @var type 
     */
    public $NumeroDocumento = "Decreto N° 41779";
    public $NombreInstitucion;
    public $FechaEmision;
    public $PorcentajeExoneracion;  //Porcentaje 0 a 100
    public $MontoExoneracion;
    
    //Tipo Documento values
    const COMPRAS = "01"; //Compras Autorizadas
    const DIPLOMATICOS = "02"; //Ventas exentas a diplomaticos
    const ESPECIAL = "03"; //Autorizado por Ley Especial
    const EXENCIONES = "04"; //Exenciones Direccion General de Hacienda
    const TRANSITORIO5 = "05"; //Transitorio V
    const TRANSITORIO9 = "06"; //Transitorio IX
    const TRANSITORIO17 = "07"; //Transitorio XVII
    const OTROS = "99"; //Otros
    
    public static function getTypes() {
        $reflectionClass = new \ReflectionClass(__CLASS__);
        return $reflectionClass->getConstants();
    }
}

class ImpuestoType {
    
    //Codigo values
    const VENTAS = "01"; //Impuesto al valor agregado
    const CONSUMO = "02"; //Impuesto Selectivo de Consumo
    const COMBUSTIVOS = "03"; //Impuesto Unico a los combustivos
    const ALCOHOLICAS = "04"; //Impuesto especifico de bebidas alcoholicas
    const ENVASADOS = "05"; //Impuesto especifico sobre las bebidas envasadas sin contenido alcoholico y jabones de tocador
    const TABACO = "06"; //Impuesto a los productos de tabaco
    const SERVICIOS = "07"; // IVA (cálculo especial)
    const DIPLOMATICOS = "08"; //IVA Regimen de Bienes Usados (Factor)
    const CEMENTO = "12"; //Impuesto Especifico al cemento
    const OTROS = "99"; //Otros
    /**
     * Default '01' impuesto al valor agregado.
     * @var char(2)
     */
    public $Codigo = "07";
    
    const EXENTO = "01";  //01 Tarifa 0% (Exento)
    const REDUCED1 = "02";  //Tarifa Reducida 1%
    const REDUCED2 = "03";  //Tarifa reducida 2%
    const REDUCED4 = "04";  //Tarifa reducida 4%
    const TRANS0 = "05";  //Transitorio 0%
    const TRANS4 = "06";  //Transitorio 4%
    const TRANS8 = "07";  //Transitorio 8%
    const IVA = "08";  //Tarifa General 13%
    /**
     * Código de la tarifa del impuesto. Default '08' full IVA
     * @var char(2) 
     */
    public $CodigoTarifa = "08";
    
    public $Tarifa = 13;  //porcentaje 0 to 100
    public $FactorIVA = null; //Opcional, no idea para que es todavia
    public $Monto;  //subtotal linea detalle por tarifa impuesto    
    /**
     * Use it when CodigoTarifa is 01 (exento)
     * @var ExoneracionType
     */
    public $Exoneracion = null;
    
    /**
     * 
     * @param type $codigoTarifa
     * @param type $monto
     */
    public function __construct($codigo = ImpuestoType::SERVICIOS, $codigoTarifa = ImpuestoType::EXENTO, $monto = 0) {
        $this->Codigo = $codigo;
        $this->CodigoTarifa = $codigoTarifa;
        $this->Tarifa = $this->getTarifa($codigoTarifa);
        $this->Monto = $monto;
    }
    
    public static function getTypes() {
        $reflectionClass = new \ReflectionClass(__CLASS__);
        return $reflectionClass->getConstants();
    }
    
    public function getTarifa($codigoTarifa) {
        switch($codigoTarifa) {
            case ImpuestoType::EXENTO: return 0;
            case ImpuestoType::REDUCED1: return 1;
            case ImpuestoType::REDUCED2: return 2;
            case ImpuestoType::REDUCED4: return 4;
            case ImpuestoType::TRANS0: return 0;
            case ImpuestoType::TRANS4: return 4;
            case ImpuestoType::TRANS8: return 8;
            case ImpuestoType::IVA: return 13;
        }
        throw new \Exception("Invalid CodigoTarifa");
    }
    
    public function process() {
        if (empty($this->Codigo) || strlen($this->Codigo) != 2) {
            throw new \Exception("Invalid Codigo: $this->Codigo");
        }
        if (empty($this->CodigoTarifa) || strlen($this->CodigoTarifa) != 2) {
            throw new \Exception("Invalid CodigoTarifa: $this->CodigoTarifa");
        }
        $this->Tarifa = $this->getTarifa($this->CodigoTarifa);  //make sure tarifa matches codigoTarifa
        if (empty($this->FactorIVA)) {
            unset($this->FactorIVA);
        }
        if (empty($this->Exoneracion)) {
            unset($this->Exoneracion);
        }
    }
    
}

class DescuentoType {
    public $MontoDescuento;
    public $NaturalezaDescuento;  //texto
    
    /**
     * 
     * @param type $MontoDescuento
     * @param type $NaturalezaDescuento
     */
    public function __construct($MontoDescuento, $NaturalezaDescuento) {
        $this->MontoDescuento = $MontoDescuento;
        $this->NaturalezaDescuento = $NaturalezaDescuento;
    }
}

class OtrosCargosType {
    public $TipoDocumento;
    public $NumeroIdentidadTercero = null;
    public $NombreTercero = null;
    public $Detalle;
    public $Porcentaje = null;
    public $MontoCargo;
    
    const PARAFISCAL = "01"; //Contribución parafiscal
    const CRUZ_ROJA = "02";  //Timbre de la Cruz Roja
    const BOMBEROS = "03";   //Timbre de Benemérito Cuerpo de Bomberos de Costa Rica
    const TERCERO = "04";    //Cobro de un tercero
    const EXPORTACION = "05";//Costos de Exportación
    const SERVICIO = "06";   //Impuesto de Servicio 10
    const COLEGIOS = "07";   //Timbre de Colegios Profesionales
    const OTROS = "99";      //Otros Cargos
    
    public static function getTypes() {
        $reflectionClass = new \ReflectionClass(__CLASS__);
        return $reflectionClass->getConstants();
    }
}

class CodigoMonedaType {
    public $CodigoMoneda;  //ISO Code
    public $TipoCambio;
    
    /**
     * 
     * @param type $codigoMoneda
     * @param type $tipoCambio
     */
    public function __construct($codigoMoneda, $tipoCambio) {
        $this->CodigoMoneda = $codigoMoneda;
        $this->TipoCambio = $tipoCambio;
    }
}

class LineaDetalle {
    public $NumeroLinea;
    public $PartidaArancelaria = null;
    public $Codigo = null;
    /**
     * @var CodigoType
     */
    public $CodigoComercial = null;
    public $Cantidad = 1;
    /**
     * Default: 'Sp' servicios profesionales, 'Unid' when sold by units, otherwise use ISO units
     * @var string
     */
    public $UnidadMedida = "Sp";
    public $UnidadMedidaComercial = null;
    public $Detalle = "";
    public $PrecioUnitario = 0;
    public $MontoTotal;
    /**
     *
     * @var DescuentoType
     */
    public $Descuento = null;
    public $SubTotal = 0;
    public $BaseImponible = null;
    /**
     * @var ImpuestoType
     */
    public $Impuesto = null;
    public $ImpuestoNeto = null;
    public $MontoTotalLinea = 0;

    
    /**
     * Constructs a new invoice line.
     * @param type $Codigo
     * @param type $Cantidad
     * @param type $Detalle
     * @param type $PrecioUnitario
     * @param type $UnidadMedida
     */
    public function __construct($Cantidad, $Detalle, $PrecioUnitario, $UnidadMedida = "Sp") {
        $this->Cantidad = $Cantidad;
        $this->Detalle = $Detalle;
        $this->PrecioUnitario = $PrecioUnitario;
        $this->UnidadMedida = $UnidadMedida;
    }
    
    public function process() {
        if ($this->Detalle == "" || $this->PrecioUnitario <= 0) {
            throw new \Exception("Detalle or PrecioUnitario missing");
        }
        
        $this->MontoTotal = $this->Cantidad * $this->PrecioUnitario;
        if (empty($this->Descuento)) {
            unset($this->Descuento);
            $this->SubTotal = $this->MontoTotal;
        } else {
            $this->SubTotal = $this->MontoTotal - $this->Descuento->MontoDescuento;
        }
        $totalImpuestos = 0;
        if (!empty($this->Impuesto)) {
            $this->Impuesto->process();
            if (empty($this->Impuesto->Monto)) {
                $this->Impuesto->Monto = $this->SubTotal * ($this->Impuesto->Tarifa / 100);
            }
            $totalImpuestos += $this->Impuesto->Monto;
        } else {
            unset($this->Impuesto);
        }
        $this->MontoTotalLinea = $this->SubTotal + $totalImpuestos;
        if (empty($this->Codigo)) {
            unset($this->Codigo);
        }
        if (empty($this->UnidadMedidaComercial)) {
            unset($this->UnidadMedidaComercial);
        }
        if (empty($this->BaseImponible)) {
            unset($this->BaseImponible);
        }
        if (empty($this->ImpuestoNeto)) {
            unset($this->ImpuestoNeto);
        }
        if (empty($this->CodigoComercial)) {
            unset($this->CodigoComercial);
        }
        if (empty($this->PartidaArancelaria)) {
            unset($this->PartidaArancelaria);
        }
    }
}

class ResumenFactura {
    /**
     *
     * @var CodigoMonedaType
     */
    public $CodigoTipoMoneda = null;
    public $TotalServGravados = 0;
    public $TotalServExentos = 0;
    public $TotalServExonerado = 0;
    public $TotalMercanciasGravadas = 0;
    public $TotalMercanciasExentas = 0;
    public $TotalMercExonerada = 0;
    public $TotalGravado = 0;
    public $TotalExento = 0;
    public $TotalExonerado = 0;  //$TotalServExonerado + $TotalMercExonerada
    public $TotalVenta = 0;
    public $TotalDescuentos = 0;
    public $TotalVentaNeta = 0;
    public $TotalImpuesto = 0;
    public $TotalIVADevuelto = 0;
    public $TotalOtrosCargos = 0;
    public $TotalComprobante = 0;
    
    /**
     * 
     * @param type $hasTaxedMerchandise 
     * @param type $hasTaxedServices
     */
    public function process($hasTaxedMerchandise = false, $hasTaxedServices = false) {
        $props = get_object_vars($this);
        foreach($props as $propName => $val) {
            if (($hasTaxedMerchandise && $propName === "TotalMercanciasGravadas") ||
                ($hasTaxedServices && $propName === "TotalServGravados") ||
                (($hasTaxedMerchandise || $hasTaxedServices) && $propName === "TotalGravado")) {  //Define this attributes even if their values are 0.0
                continue;
            }
            if (empty($this->$propName)) {
                unset($this->$propName);
            }
        }
    }
}

class Otros {
    public $OtroTexto = null;
    public $OtroContenido = null;
}

class InformacionReferencia {
    
    const CANCEL = "01";  //Cancels referenced document
    const CORRECT_TEXT = "02";  //Corrects text of reference document
    const CORRECT_TOTAL = "03"; //Corrects totals
    const REF_DOC = "04";  //References another document
    const CONTINGENCY = "05";  //Substitutes provisional ticket per contingency
    const OTHER = "99";
    
    /**
     * Tipo de documento de referencia:
     *  01 Factura electrónica
     *  02 Nota de débito electrónica
     *  03 nota de crédito electrónica
     *  04 Tiquete electrónico
     *  05 Nota de despacho
     *  06 Contrato
     *  07 Procedimiento
     *  08 Comprobante emitido en contigencia
     *  09 Devoluicion mercaderia
     *  10 Sustituye factura rechazada por Ministerio de Hacienda
     *  11 Sustituye factura rechazada por el receptor del comprobante
     *  12 Sustituye factura de exportacion
     *  13 facturacion mes vencido
     *  99 otros
     * @var char(2)
     */
    public $TipoDoc;
    /**
     * This is the Clave of the referenced document.
     * @var string
     */
    public $Numero;
    /**
     * This is the original fecha de emsision of the referenced document.
     * @var string
     */
    public $FechaEmision;
    /**
     * Reference code, any of the above constant values.
     * @var string
     */
    public $Codigo;
    public $Razon;
    
}

class FacturaElectronica {
    public $Clave;  //Length {50,50}
    /**
     * Code of Economical Activity as per Hacienda ATV
     * @var char(6)
     */
    public $CodigoActividad;
    public $NumeroConsecutivo;  //Length {20,20}
    public $FechaEmision;
    /**
     * @var EmisorType
     */
    public $Emisor = null;
    /**
     * @var ReceptorType
     */
    public $Receptor = null;
    /**
     * Condiciones de la venta: 
     *  01 Contado
     *  02 Credito
     *  03 Consignacion
     *  04 Apartado
     *  05 Arrendamiento con opcion de compra
     *  06 Arrendamiento en funcion financiera
     *  07 Cobro a favor de un tercero
     *  08 servicxios prestados al estado a credito
     *  09 pago del servicio prestado al estado
     *  99 Otros
     * @var string 
     */
    public $CondicionVenta = "01";
    public $PlazoCredito = 0;
    /**
     * Corresponde al medio de pago empleado: 
     *  01 Efectivo
     *  02 Tarjeta
     *  03 Cheque
     *  04 Transferencia - deposito bancario
     *  05 Recaudado por terceros
     *  99 Otros
     * @var string
     */
    public $MedioPago = "01";
    /**
     * Array of
     * @var LineaDetalle
     */
    public $DetalleServicio = null;
    /**
     * Array of
     * @var OtrosCargosType
     */
    public $OtrosCargos = null;
    /**
     * @var ResumenFactura
     */
    public $ResumenFactura = null;
    /**
     *
     * @var InformacionReferencia
     */
    public $InformacionReferencia = null;
    /**
     *
     * @var Otros
     */
    public $Otros = null;
    
    public function __construct() {
        $this->FechaEmision = date("c");  //ISO 8601 date
    }
    
    /**
     * Generates a consecutive string following the format defined in Resolution DGT-R-48-2016
     * AAABBBBBCCDDDDDDDDDD
     * With:
     *  A local/branch number
     *  B terminal number
     *  C document type
     *  D consecutive number
     * @param int $consecutive
     * @param IDP $docType
     * @param int $local
     * @param int $terminal
     * @return string
     */
    public static function generateConsecutivo($consecutive, $docType = IDP::FE, $local = 1, $terminal = 1) {
        $local = str_pad($local, 3, "0", STR_PAD_LEFT);
        $terminal = str_pad($local, 5, "0", STR_PAD_LEFT);
        $docType = str_pad($docType, 2, "0", STR_PAD_LEFT);
        $consecutive = str_pad($consecutive, 10, "0", STR_PAD_LEFT);
        return $local . $terminal . $docType . $consecutive;
    }
    
    /**
     * Generates a key string following the format defined in Resolution DGT-R-48-2016
     * AAABBCCDDEEEEEEEEEEEEFFFFFFFFFFFFFFFFFFFFGHHHHHHHH
     *  A Country code (usually 506)
     *  B day of generation
     *  C month of generation
     *  D year of generation
     *  E Identificacion emisor
     *  F Consecutivo
     *  G Document state (1 normal, 2 contingency, 3 no internet)
     *  H security code
     * @param type $Consecutivo
     * @param type $securityCode
     * @param type $identEmisorNumero
     * @param type $countryCode
     * @param type $day
     * @param type $month
     * @param type $year
     * @param type $state
     * @return type
     */
    public static function generateClave($Consecutivo, $securityCode, $identEmisorNumero, $countryCode = "506", $day = 0, $month = 0, $year = 0, $state = 1) {
        $day =   $day == 0   ? date("d") : $day;
        $month = $month == 0 ? date("m") : $month;
        $year =  $year == 0  ? date("y") : $year;
        $countryCode = str_pad($countryCode, 3, "0", STR_PAD_LEFT);
        $day = str_pad($day, 2, "0", STR_PAD_LEFT);
        $month = str_pad($month, 2, "0", STR_PAD_LEFT);
        $year = str_pad($year, 2, "0", STR_PAD_LEFT);
        $identEmisorNumero = str_pad($identEmisorNumero, 12, "0", STR_PAD_LEFT);
        $Consecutivo = str_pad($Consecutivo, 20, "0", STR_PAD_LEFT);
        $securityCode = str_pad($securityCode, 8, "0", STR_PAD_LEFT);
        return $countryCode . $day . $month . $year . $identEmisorNumero . $Consecutivo . $state . $securityCode;
    }
    
    public function addDetalle(LineaDetalle $det) {
        if ($this->DetalleServicio == null) {
            $this->DetalleServicio = [];
        }
        $this->DetalleServicio[] = $det;
    }
    
    public function process() {
        if ($this->ResumenFactura == null or $this->DetalleServicio == null) {
            throw new \Exception("ResumenFactura or DetalleServicio missing");
        }
        
        if (strlen($this->CodigoActividad) != 6) {
            throw new \Exception("Invalid length for CodigoActividad");
        }
        
        $linNum = 1;
        $hasTaxedServices = false;
        $hasTaxedMerchandise = false;
        if (is_array($this->DetalleServicio) && count($this->DetalleServicio) > 0) {
            $DetalleServicio = [];
            foreach($this->DetalleServicio as $det) {
                $det->process();
                $det->NumeroLinea = $linNum++;
                $this->ResumenFactura->TotalVenta += $det->MontoTotal;
                if (isset($det->Descuento)) {
                    $this->ResumenFactura->TotalDescuentos += $det->Descuento->MontoDescuento;
                }
                if (isset($det->Impuesto) && $det->Impuesto != null) {
                    if (empty($det->BaseImponible)) {
                        $det->BaseImponible = $det->SubTotal;
                    }
                    if ($det->UnidadMedida == "Sp") {  //services
                        $hasTaxedServices = true;
                        $this->ResumenFactura->TotalServGravados += $det->MontoTotal;
                    } else {  //merchandise
                        $hasTaxedMerchandise = true;
                        $this->ResumenFactura->TotalMercanciasGravadas += $det->MontoTotal;
                    }
                    $this->ResumenFactura->TotalImpuesto += $det->Impuesto->Monto;
                } else {
                    if ($det->UnidadMedida == "Sp") {
                        $this->ResumenFactura->TotalServExentos += $det->MontoTotal;
                    } else {
                        $this->ResumenFactura->TotalMercanciasExentas += $det->MontoTotal;
                    }
                }
                $DetalleServicio[] = ["LineaDetalle" => $det];
            }
            $this->DetalleServicio = $DetalleServicio;
        } else {
            throw new \Exception("DetalleServicio cannot be empty");
        }
        
        $this->ResumenFactura->TotalGravado = $this->ResumenFactura->TotalMercanciasGravadas + $this->ResumenFactura->TotalServGravados;
        $this->ResumenFactura->TotalExento = $this->ResumenFactura->TotalMercanciasExentas + $this->ResumenFactura->TotalServExentos;
        $this->ResumenFactura->TotalVentaNeta = $this->ResumenFactura->TotalVenta - $this->ResumenFactura->TotalDescuentos;
        $this->ResumenFactura->TotalComprobante = $this->ResumenFactura->TotalVentaNeta + $this->ResumenFactura->TotalImpuesto;
        
        $this->ResumenFactura->process($hasTaxedMerchandise, $hasTaxedServices);
        
        if ($this->CondicionVenta != "02") {  //not credit
            unset($this->PlazoCredito);
        }
        if (empty($this->Otros)) {
            unset($this->Otros);
        }
        if (empty($this->Emisor)) {
            throw new \Exception("Emisor cannot be null");
        } else {
            $this->Emisor->process();
        }
        if (!empty($this->Receptor)) {
            $this->Receptor->process();
        } else {
            unset($this->Receptor);
        }
        if (empty($this->OtrosCargos)) {
            unset($this->OtrosCargos);
        } else {
            foreach($this->OtrosCargos as $cargo) {
                $this->ResumenFactura->TotalOtrosCargos += $cargo->MontoCargo;
            }
        }
        if (empty($this->InformacionReferencia)) {
            unset($this->InformacionReferencia);
        }
    }
}

class FacturaElectronicaExportacion extends FacturaElectronica {
    //No differences
}

class NotaCreditoElectronica extends FacturaElectronica {
    
    public function __construct() {
        parent::__construct();
        $Otros = $this->Otros;
        unset($this->Otros);
        $this->Otros = $Otros;
    }
    
}

class FacturaElectronicaCompra extends FacturaElectronica {
    
    public function __construct() {
        parent::__construct();
        $Otros = $this->Otros;
        unset($this->Otros);
        $this->Otros = $Otros;
    }
}

class MensajeReceptor {
    
    /**
     * Clave del documento que se esta aceptando.
     * @var type \d{50,50}
     */
    public $Clave;
    /**
     *
     * @var \d{12,12}
     */
    public $NumeroCedulaEmisor;
    /**
     * Fecha de emisión del comprobante de aceptacion.
     * @var ISO_Date
     */
    public $FechaEmisionDoc;
    /**
     * Codigo de aceptacion: 1 aceptado, 2 parcialmente aceptado, 3 rechazado
     * @var int
     */
    public $Mensaje;
    /**
     * Opcional. Detalle del mensaje.
     * @var varchar(80)
     */
    public $DetalleMensaje;
    /**
     * Opcional. Monto total del impuesto, que es obligatorio si el comprobante tiene impuesto.
     * @var decimal
     */
    public $MontoTotalImpuesto;
    /**
     * Monto total de la factura aceptada.
     * @var decimal
     */
    public $CodigoActividad = null;
    /**
     * Condición del IVA:
     *  01 General Credito IVA
     *  02 General Crédito parcial del IVA
     *  03 Bienes de Capital
     *  04 Gasto corriente no genera crédito
     *  05 Proporcionalidad
     * @var string 
     */
    public $CondicionImpuesto = null;
    public $MontoTotalImpuestoAcreditar = null;
    public $MontoTotalDeGastoAplicable = null;
    public $TotalFactura;
    /**
     * Numero de cedula del receptor.
     * @var \d{12,12}
     */
    public $NumeroCedulaReceptor;
    /**
     * Consecutivo del comprobante de aceptacion.
     * @var \d{20,20} 
     */
    public $NumeroConsecutivoReceptor;
    
    public function process() {
        if (empty($this->MontoTotalDeGastoAplicable)) {
            unset($this->MontoTotalDeGastoAplicable);
        }
        if (empty($this->MontoTotalImpuestoAcreditar)) {
            unset($this->MontoTotalImpuestoAcreditar);
        }
        if (empty($this->CondicionImpuesto)) {
            unset($this->CondicionImpuesto);
        }
        if (empty($this->CodigoActividad)) {
            unset($this->CodigoActividad);
        }
        if (empty($this->MontoTotalImpuesto)) {
            unset($this->MontoTotalImpuesto);
        }
        if (empty($this->DetalleMensaje)) {
            unset($this->DetalleMensaje);
        }
        if ($this->Mensaje < 1 or $this->Mensaje > 3) {
            throw new \Exception("Mensaje must be a value between 1 and 3");
        }
        if (strlen($this->NumeroCedulaEmisor) > 12) {
            throw new \Exception("NumeroCedulaEmisor is in the wrong format");
        }
        if (strlen($this->NumeroCedulaReceptor) > 12) {
            throw new \Exception("NumeroCedulaReceptor is in the wrong format");
        }
        if (empty($this->MontoTotalImpuesto)) {
            unset($this->MontoTotalImpuesto);
        }
        //Cedulas deben tener estrictamente 12 caracteres
        $this->NumeroCedulaEmisor   = str_pad($this->NumeroCedulaEmisor,   12, "0", STR_PAD_LEFT);
        $this->NumeroCedulaReceptor = str_pad($this->NumeroCedulaReceptor, 12, "0", STR_PAD_LEFT);
        $this->FechaEmisionDoc = date("c");
    }
    
}
