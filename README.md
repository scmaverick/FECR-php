# FECR-php
Libraries, classes and methods for generating, signing and submitting electronic invoice using Hacienda API v4.3 Costa Rica.

I use these code in production in a software I built for travel agencies (see more here www.crtouroperator.com).
All I'm sharing are the libraries, classes and methods for the functionality listed above, you need to develop your own UIs, your own DB schema, basically your own software, these libraries however, will make your life much easier by not having to care much about the underlying structure of the XML (all classes here are based on PHP standard classes and objects), I'm also including the methods for signing the XML using your own cryptographic key as provided by Hacienda (no support for digital signature yet although I suspect it wouldn't be much different).

Basically, just include the files in your PHP project and start using them as you would any other library.
You need to know a little bit about the order of things though, and here I will include a very short tutorial with some demo code that should help you get up to speed.

### Some recommendations first:
- I started this project on FE v4.2 but later updated it to v4.3 of electronic invoice, I'm based on the structures defined at the [official site of hacienda](https://www.hacienda.go.cr/ATV/ComprobanteElectronico/frmAnexosyEstructuras.aspx), I strongly recommend you take a look at it in case you haven't.
- Familiarize yourself with the two most important (and only really) endpoints from the API, check [Swagger](https://www.hacienda.go.cr/ATV/ComprobanteElectronico/docs/esquemas/2016/v4.1/comprobantes-electronicos-api.html), the libraries here will perform all the connections for you but you still need to know what's on the back, also in case those endpoints change you might want to update the URLs.
- Register your account on the ATV (Administracion Tributaria Virtual) site of [Hacienda](https://www.hacienda.go.cr/ATV/Login.aspx), you'll need it to get your API user, secret and crypto key.
- Even though Hacienda claims their API can handle up to 1000 requests per second and will auto scale when needed I sincerely believe their implementation just s*cks, cause the number of errors I have hit against so far from their end is just ridiculous, and the Staging API is way worse, at some points is even completely useless, but hey, is what you got and we still need to test against it, unless you want to mess with them and generate invoices just to void them with credit notes for your testing. Anyway, I just mention this because for this same reason is that I'm trying to add as much validation to the XML generation as possible (it's still lacking though) to prevent failures, and you should do the same.
- Check out this site, super valuable and strongly recommended https://apis.gometa.org/
- Write your code considering always that you will be 100% of the time in contingency mode, Hacienda API is so unstable that this may very well be the truth. So, implement retry mechanisms, scheduled resend jobs, etc. Save your invoices so that the XML can be recreated at any point and always save the final generated XML and all Hacienda responses (it's required by the law anyway).
- Don't try to cheat, don't let your users cheat, us Costa Ricans are so creative that we could get in trouble very easily.

### Caveats
- The code here may not be perfect, in fact, it may be very far from it, but if you are willing to provide constructive criticism I'm all ears, having said so, take it as it is with no warranties, support or (frequent) maintenance, if you like it and we ever meet may you consider buying me a beer?
- This is not a 100% implementation of electronic invoice, mainly because I don't need it all, although I've tried to cover most aspects of the law, especially considering the new Fiscal Reform which puts a tax on everything and anything. If you see that something is missing and you can add it to the code and would like to submit a pull request to have it updated you're welcome.
- Forgot to mention, I am in no way related to the government, Hacienda, the ESPH or any other public entity, yes I have operational commercial software that uses these libraries for my own benefit which is mainly why I'm giving this up for free (to try to save others from great suffering), but other than the link mentioned on the description there's no indication or pieces left from that software on these libraries (I hope).
- This only works with electronic invoice Costa Rica! Doesn't mean you can't modify it and make it fit, perhaps that's easier than writing it all from scratch.
- My email is included, but it doesn't mean that I provide support or respond to questions, I may, or I may not, if your question is smart and properly written I probably would anyway.
- In some places I'm using "or" instead of || or "and" instead of &&, I'm trying to change that as it was an old practice from older PHP versions, I first started with PHP back in 2006. It doesn't affect the code at all (I've tested it hundreds of times) but don't you follow this practice unless you know how to deal with operators order.
- Yes, there's a spanglish mix in the code because I'm using the literal attribute names from the XML in the class models, no transformations or transliterations of any kind in order to make it clearer (I hate it when people change names and is not clear enough whether you're referencing the same things or not).

### Other notes
- I took some pieces of code from other github projects like CRLibre (thank you!), mainly the XML signature piece because it was so complicated, I know that some people don't like this signing method (hardcoding tags and labels) but it works fine so, whatever, there are some other signers written in Java and .Net that you can use if you feel like so (look somewhere else).
- Some methods were designed to throw Exceptions, so expect them, wrap your code in try/catches and so.
- I use Apache Netbeans on Linux, not like it changes anything but incase you were wondering, I just can't live without code auto-completion.
- Feel free to explore the code, I've included some comment tags especially for public methods but not for everything, if something is too obscure is probably cause it just works and I didn't care (don't judge me :D)
- I'm including an ISO 4217 currency codes library in case you find it useful.
- For getting the list of provinces, cantons, districts I'm using an external [API service](https://programando.paginasweb.cr/2016/04/29/lista-de-provincias-cantones-y-distritos-de-costa-rica-en-formato-json/), if you don't want to use it don't use the getters from UbicacionType class.
- All classes were embedded in the FECR namespace to prevent collisions.

## FAQs
- Why write this in English?
Because English is the language of programming, because every time I search google for something it's in English and I get results in English, because technical language exists in English not in Spanish, because get used to it.
- Need Spanish version?
[Here](https://translate.google.com/)
- Even though you said you wouldn't provide maintenance, will you provide updates anyway?
Probably yes, but only if I need to make updates to my software because the government or Hacienda came up with some "innovation" or because I found some bug or just found something that could work better. Or if some good samaritans submits some good commits.
- What does the process() method do in some class models?
It is called internally to validate attributes and values from those classes, in some cases it performs some number value calculations, this is to try to catch as much errors as possible before sending the document to hacienda, usually you don't have to call them manually but you may see Exceptions thrown from it.
- How does the defaultLogger work?
APIHacienda class will log errors using this default function which you can override by calling the registerLogger() method, your override function should receive 2 parameters ($msg, $mode) where $msg is the error message and $mode is one of IDP::ERROR, IDP::DEBUG or IDP::INFO.
- Does this support persisten sessions with Hacienda API?
No, unless you write code for this yourself, otherwise these methods were thought to request a new authentication token for every new call to the API, the token ony lasts for about 5 mins anyway, but yes, I wasn't expecting to use refresh tokens or reusing tokens.
- Is this really just individual floating libraries?
Yes, if you wanted a service write it yourself, there's plenty of examples and tutorials on how to write any type of software on the internet, but there's very little information on how to properly create, sign and submit an XML electronic invoice in Costa Rica and that's why I've written this classes, you should know programming, you should know php, I trust you can build the rest.

### Dependencies
- PHP 5.6 (may work with newer versions, haven't tested)
- php-curl
- php-openssl
- php-xml
- Anything else?

# Getting Started
Finally! Yes, sorry for all the bs above. Here's the quick demo, from this code and on you can probably deduce the rest of the functionality.

```
//Notice I'm assuming this is running behind a web server like Apache but that is not a requirement, copy/paste and modify.

require_once './FECR_APIHacienda.php';
require_once './FECR_Classes.php';
require_once './FECR_Xades.php';
require_once './FECR_XML.php';

//Modifiable Settings
date_default_timezone_set("America/Costa_Rica");
$print = true;
$dryRun = false;
$facNum = 10;
$apiUsername = "cpj-3-101-XXXXXX@stag.comprobanteselectronicos.go.cr";
$apiPassword = "*************";
$compName = "3 Patitos";
$identNumber = "3101XXXXXX";
$countryCode = "506";
$compPhone = "XXXXXXXX";
$compEmail = "info@3patitos.com";
$compProvince = 1;
$compCanton = 1;
$compDistrict = 1;
$certFile = "/my/path/to/cert/file/api-stg.p12";  //or Windows path
$certKey = "XXXX";
//!Settings

$idp = null;
if (!$dryRun) {
	//Authenticate with Hacienda and retrieve access token
	$idp = new APIHacienda(IDP::STAGING, IDP::INFO);  //Connect to Staging API and log at the INFO level
	$idp->setCredentials($apiUsername, $apiPassword);
	if (!$idp->requestAccessToken()) {  //The token is used internally by the object, you don't need it
		die("<b><b>Unable to retrieve access token.");
	}
} else {
	echo "<b>Running in dry mode (no IDP connection)</b><br><br>\n\n";
}

if (isset($_GET['comprobantes'])) {
	if ($dryRun) {
		die("Cannot run in dry mode.");
	}
	$emisorIdent = new IdentificacionType($identNumber, IdentificacionType::CED_JURIDICA);
	$resp = $idp->getComprobantes(0, $emisorIdent); //retrieve all comprobantes for the given cedula
	if ($print) {
		echo "<b>Tickets:</b><br><br>\n\n";
		print_r($resp);
	}
}

if (isset($_GET["estado"])) {
	if ($dryRun) {
		die("Cannot run in dry mode.");
	}
	$clave = "X x50";
	$resp = $idp->getComprobanteStatus($clave);
	if ($print) {
		print("<b>Ticket status:</b><br><br>\n\n");
		print_r($resp);
		$rx = "/\"respuesta\-xml\"\s+\:\s+\"([^\"]+)\"/";
		if (preg_match($rx, $resp["bodyText"], $m) === 1) {
			echo "</b><br><br>\n\n<b>Decoded response:</b><br><br>\n\n";
			echo base64_decode($m[1]);
		}
	}
}

if (isset($_GET["enviar"])) {
	//Generate invoice
	$data = new FacturaElectronica();  //Just create a new object of the type needed (factura, nota credito, etc) and the class will traduce it to XML when needed
	$data->NumeroConsecutivo = $data->generateConsecutivo($facNum);
	$data->CodigoActividad = "XXXXXX";
	$data->Clave = $data->generateClave($data->NumeroConsecutivo, $facNum, $identNumber, $countryCode);

	$emisor = new EmisorType();
	$emisor->Nombre = $compName;
	$emisor->Identificacion = new IdentificacionType($identNumber, IdentificacionType::CED_JURIDICA);
	$emisor->Telefono = new TelefonoType($countryCode, $compPhone);
	$emisor->CorreoElectronico = $compEmail;
	$ubicacion = new UbicacionType();
	$ubicacion->Provincia = $compProvince;
	$ubicacion->Canton = $compCanton;
	$ubicacion->Distrito = $compDistrict;
	$ubicacion->OtrasSenas = "Edificio Negro";
	$emisor->Ubicacion = $ubicacion;
	$data->Emisor = $emisor;

	$resumen = new ResumenFactura();
	$resumen->CodigoTipoMoneda = new FECR\CodigoMonedaType("USD", true), "600.00");
	$data->ResumenFactura = $resumen;

	$data->addDetalle(new LineaDetalle(1, "Servicios Exentos", 150));  //quantity, detail, price
	$data->addDetalle(new LineaDetalle(1, "Servicios Exentos", 200));
	$ld = new LineaDetalle(1, "Servicios Gravados", 100);
	$ld->Impuesto = new ImpuestoType(ImpuestoType::IVA, ImpuestoType::SERVICIOS);  //Services IVA, the class will calculate the tax factor automatically
	$data->addDetalle($ld);

	//Create XML
	$unsignedXML = "";
	try {
		$xml = new XML();
		$unsignedXML = $xml->genXML($data);  //And so we request the class to transform our beautiful objects into ugly XML (unsigned at this step)
	} catch (\Exception $ex) {
		die("<b><b>Error generating XML: " . $ex);
	}

	if ($print) {
		echo "<b>Unsigned XML</b></br></br>\n\n";
		echo $unsignedXML;
	}
	
	//Sign XML invoice
	try {
		$fe = new FirmadorCR();
		$signedXML = $fe->firmar($certFile, $certKey, $unsignedXML);  //And request the class to sign your XML with your crypto key
	} catch (\Exception $ex) {
		die("<b><b>Error signing XML: " . $ex);
	}
	if ($print) {
		echo "</br></br>\n\n<b>Signed and encoded XML</b></br></br>\n\n";
		echo $signedXML . "</br></br>\n\n";
	}

	//Submit Signed XML to Hacienda
	if (!$dryRun) {
		try {
			$resp = $idp->sendTE($signedXML, $data);  //Now we're ready to send to Hacienda, so just do it!
			if ($print) {
				echo "</br></br>\n\n<b>Response</b>\n\n";
				print_r($resp);
			}
		} catch (\Exception $ex) {
			die("<b><b>Error sending document: " . $ex);
		}
	}

	if ($print) {
		echo "</br></br>\n\n<b><b>Done!";
	}
}

if (!$dryRun) {
	$idp->logout();  //dispose auth token
}
```
And that's it, as simple as that. Ain't it pretty?
Now get coding, and if you really like this write me or send me a paycheck (even better! accepting paypal and SINPE :))

Enjoy!
Sergio
