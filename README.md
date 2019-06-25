# FECR-php
Libraries, classes and methods for generating, signing and submitting electronic invoice using Hacienda API v4.3 Costa Rica.

I use these code in production in a software I built for travel agencies (see more here www.crtouroperator.com).
All I'm sharing are the libraries, classes and methods for the functionality listed above, you need to develop your own UIs, you need DB schema, basically your own software, these libraires however, will make your life much easier by not having to care much about the underlying structure of the XML (all classes here are based on PHP standard classes and objects), I'm also including the methods for signing the XML using your own cryptographic key as provided by Hacienda (no support for digital signature yet although I suspect it wouldn't be much different).

Basically, just include the files in your PHP project and start using them as you would any other library.
You need to know a little bit about the order though and here I will include a very short tutorial with some demo code that should help you get up to speed.

### Some recommendations first:
- I started this project on FE v4.2 but later updated it to v4.3 of electronic invoice, I'm based on the structures defined on the [official site of hacienda](https://www.hacienda.go.cr/ATV/ComprobanteElectronico/frmAnexosyEstructuras.aspx), I strongly recommend to have a look at it in case you haven't.
- Familiarize yourself with the two most important (and unique really) endpoints from the API, check [Swagger](https://www.hacienda.go.cr/ATV/ComprobanteElectronico/docs/esquemas/2016/v4.1/comprobantes-electronicos-api.html), the libraries here will perform all the connections for you but you still need to know what's on the back, also in case those endpoints change you might want to update the URLs.
- Register your account on the ATV (Administracion Tributaria Virtual) site of [Hacienda](https://www.hacienda.go.cr/ATV/Login.aspx), you'll need it to get your API user, secret and crypto key.
- Even though Hacienda claims their API can handle up to 1000 requests per second and will auto scale when needed I sincerely believe their implementation is just wrong, cause the number of errors I have hit against so far from their end is just ridiculous, and the Staging API is way worse, at some points is even completely useless, but hey, is what you got and we still need to test against it, unless you want to mess with them and generate invoices just to void them with credit notes for your testing. Anyway, I just mention this because for this same reason is that I'm trying to add as much validation to the XML generation as possible (is still very short though) to prevent failures, and you should do the same.
- Check out this site, super valuable and strongly recommended https://apis.gometa.org/
- Write your code considering always that you will be 100% of the time in contingency mode, Hacienda API is so unstable that this may very well be the truth. So, implement retry mechanisms, scheduled resend jobs, etc. Save your invoices so that the XML can be recreated at any point and always save the final generated XML and all Hacienda responses (it's required by the law anyway).
- Don't try to cheat, don't let your users cheat, us Costa Ricans are so creative that we could get in trouble very easily.

### Caveats
- The code here may not be perfect, in fact, it may be very far, but if you are willing to provide constructive criticism I'm all ears, having said so, take it as it is with no warranties, support or (frequent) maintenance, if you like it and we ever meet may you consider buying me a beer?
- This is not a 100% implementation of electronic invoice, mainly because I don't need it all, although I've tried to cover most aspects of the law, especially considering the new Fiscal Reform which puts a tax on everything and anything. If you see that something is missing and you can add it to the code and would like to submit a pull request to have it updated it you're welcome.
- Forgot to mention, I am in no way related to the government, Hacienda, the ESPH or any other public entity, yes I have operational commercial software that uses these libraries for my own benefit which is mainly why I'm giving it up for free, but other than the link mentioned before there's no indication of pieces left in the software hereby provided (I hope).
- This only works with electronic invoice Costa Rica! Doesn't make you can't modify it and make it fit, perhaps that's easier than writing it all from scratch.
- My email is included, but it doesn't mean that I provide support or respond to questions, I may, or I may not, if your question is properly written I probably would anyway.

### Other notes
- I took some pieces of code from other github projects like CRLibre (thank you!), mainly the XML signature piece because it was so complicated, I know that some people don't like this signing method (hardcoding tags and labels) but it works fine so, whatever, there are some other signers written in Java and .Net that you can use if you feel like so (look somewhere else).
- Some methods were designed to throw Exceptions, so expect them, wrap your code in try/catches and so.
- I use Apache Netbeans, not like it changes anything but incase you were wondering, I just can't live without code auto-completion.
- Feel free to explore the code, I've included some comment tags especially for public methods but not for everything, if something is too obscure is probably cause it just works and I didn't care (don't judge me :D)
- I'm including an ISO 4217 currency codes library in case you find it useful.
- For getting the list of provinces, cantons, districts I'm using an external [API service](https://programando.paginasweb.cr/2016/04/29/lista-de-provincias-cantones-y-distritos-de-costa-rica-en-formato-json/), if you don't want to use it don't use the getters from UbicacionType class.

## FAQs
- Why write this in English?
Because English is the language of programming, because every time I search google for something it's in English and I get results in English, because technical language exists in English not in Spanish, because get used to it.
- Even though you said you wouldn't provide maintenance, will you provide updates anyway?
Probably yes, but only if I need to make updates to my software because the government or Hacienda came up with some "innovation" or because I found some bug or just found something that could work better. Or if good samaritan submits some good commits.

### Dependencies
- PHP 5.6 (may work with newer versions, haven't tested)
- php-curl
- php-openssl
- php-xml

# Getting Started
Finally! Yes, sorry for all the bs above. Here's the quick demo, from this code and on you can probably deduce the rest of the functionality.


