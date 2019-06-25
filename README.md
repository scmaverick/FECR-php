# FECR-php
Libraries, classes and methods for generating, signing and submitting electronic invoice using Hacienda API v4.3 Costa Rica.

I use these code in production in a software I built for travel agencies (see more here www.crtouroperator.com).
All I'm sharing are the libraries, classes and methods for the functionality listed above, you need to develop your own UIs, you need DB schema, basically your own software, these libraires however, will make your life much easier by not having to care much about the underlying structure of the XML (all classes here are based on PHP standard classes and objects), I'm also including the methods for signing the XML using your own cryptographic key as provided by Hacienda (no support for digital signature yet although I suspect it wouldn't be much different).

Basically, just include the files in your PHP project and start using them as you would any other library.
You need to know a little bit about the order though and here I will include a very short tutorial with some demo code that should help you get up to speed.

Some recommendations first:

