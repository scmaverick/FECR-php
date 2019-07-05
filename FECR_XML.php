<?php
/**
 * A library to generate the XML needed for Factura Electronica hacienda v4.3 Costa Rica
 * By Sergio Castillo <sergio.cs87@yahoo.com>
 * Some of this code is based off API_Hacienda project by CRLibre
 * https://github.com/CRLibre/API_Hacienda/blob/master/api/contrib/genXML/xmlGenerator.php
 * Several changes have been made to remove the module format from API_Hacienda project and to make it
 * an embeddable class that receives the data from regular parameters.
 * 
 * ----------------------------------------------------------------------------
 * "THE BEER-WARE LICENSE" (Revision 42):
 * As long as you retain this notice you can do whatever you want with this 
 * stuff. If we meet some day, and you think this stuff is worth it, you can 
 * buy me a beer in return.
 * ----------------------------------------------------------------------------
 */

namespace FECR;

/**
 * Description of FECR_XML
 *
 * @author sergio
 */
class XML {
    
    private $data;
    
    function array_to_xml($array, $header) {
        global $log;
        $xml = new \SimpleXMLElement("<?xml version=\"1.0\" encoding=\"utf-8\"?>$header");
        $f = create_function('$f,$c,$a','
                foreach($a as $k => $v) {
                    if (is_array($v) and is_numeric($k)) {
                        $f($f, $c, $v);
                    } elseif (is_object($v) or is_array($v)) {
                        $ch = $c->addChild($k);
                        $f($f, $ch, $v);
                    } else {
                        $c->$k = $v;
                    }
                }');
        $f($f, $xml, $array);
        
        $dom = new \DOMDocument("1.0");
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
                
        $asXml = mb_convert_encoding($xml->asXML(), 'ISO-8859-15', 'UTF-8');  //replace special chars not accepted by UTF8
        $dom->loadXML($asXml);
        return $dom->saveXML();
    } 

    /**
     * Generates an XML document following the format defined by Hacienda.
     * Supports: facturas, tiquetes notas de debito and notas de credito.
     * 
     * @param FacturaElectronica $data
     * @param int $tipoDocumento
     * @return string
     */
    public function genXML($data, $tipoDocumento = IDP::FE) {
        $decoded = @json_decode($data);
        if ($decoded !== null) {
            $data = $decoded;
        }
        $this->data = $data;

        $data->process();

        $headerDoc = "";
        $footerDoc = "";
        if ($tipoDocumento == IDP::FE) {
            $headerDoc = '<FacturaElectronica xmlns="https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.3/facturaElectronica" '
                    . 'xsi:schemaLocation="https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.3/facturaElectronica '
                    . 'https://www.hacienda.go.cr/ATV/ComprobanteElectronico/docs/esquemas/2016/v4.3/FacturaElectronica_V4.3.xsd"';
            $footerDoc = '</FacturaElectronica>';
        } elseif ($tipoDocumento == IDP::TE) {
            $headerDoc = '<TiqueteElectronico xmlns="https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.3/tiqueteElectronico" '
                    . 'xsi:schemaLocation="https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.3/tiqueteElectronico '
                    . 'https://www.hacienda.go.cr/ATV/ComprobanteElectronico/docs/esquemas/2016/v4.3/TiqueteElectronico_V4.3.xsd"';
            $footerDoc = '</TiqueteElectronico>';
        } elseif ($tipoDocumento == IDP::NC) {
            $headerDoc = '<NotaCreditoElectronica xmlns="https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.3/notaCreditoElectronica" '
                    . 'xsi:schemaLocation="https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.3/notaCreditoElectronica '
                    . 'https://www.hacienda.go.cr/ATV/ComprobanteElectronico/docs/esquemas/2016/v4.3/NotaCreditoElectronica_V4.3.xsd"';
            $footerDoc = '</NotaCreditoElectronica>';
        } elseif ($tipoDocumento == IDP::ND) {
            $headerDoc = '<NotaDebitoElectronica xmlns="https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.3/notaDebitoElectronica" '
                    . 'xsi:schemaLocation="https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.3/notaDebitoElectronica '
                    . 'https://www.hacienda.go.cr/ATV/ComprobanteElectronico/docs/esquemas/2016/v4.3/NotaDebitoElectronica_V4.3.xsd"';
            $footerDoc = '</NotaDebitoElectronica>';
        } elseif ($tipoDocumento == IDP::FX) {
            $headerDoc = '<FacturaElectronicaExportacion xmlns="https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.3/facturaElectronicaExportacion" '
                    . 'xsi:schemaLocation="https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.3/facturaElectronicaExportacion '
                    . 'https://www.hacienda.go.cr/ATV/ComprobanteElectronico/docs/esquemas/2016/v4.3/FacturaElectronicaExportacion_V4.3.xsd"';
            $footerDoc = '</FacturaElectronicaExportacion>';
        } elseif ($tipoDocumento == IDP::FC) {
            $headerDoc = '<FacturaElectronicaCompra xmlns="https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.3/facturaElectronicaCompra" '
                    . 'xsi:schemaLocation="https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.3/facturaElectronicaCompra '
                    . 'https://www.hacienda.go.cr/ATV/ComprobanteElectronico/docs/esquemas/2016/v4.3/FacturaElectronicaCompra_V4.3.xsd"';
            $footerDoc = '</FacturaElectronicaCompra>';
        } else {
            $headerDoc = '<MensajeReceptor xmlns="https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.3/mensajeReceptor" '
                    . 'xsi:schemaLocation="https://cdn.comprobanteselectronicos.go.cr/xml-schemas/v4.3/mensajeReceptor '
                    . 'https://www.hacienda.go.cr/ATV/ComprobanteElectronico/docs/esquemas/2016/v4.3/MensajeReceptor_V4.3.xsd"';
            $footerDoc = '</MensajeReceptor>';
        }
        $headerDoc .= ' xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">';
        
        $finalXML = $this->array_to_xml($data, $headerDoc . $footerDoc);
        return $finalXML;
    }

}
