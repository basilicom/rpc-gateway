Document Authentication Plugin
================================================

Developer info: [basilicom](http://basilicom.de/)

## Synopsis

* 

## Code Example / Method of Operation

* if your service namespace ist not "\App\Rpc\Service", please don't forget to set your custom namespace.
* For Example, your Service Class is \Website\Rpc\Custom\User.php:
* $rpc = new \RpcGateway\Gateway();
* $rpc->setServiceClassNamespace("\\Website\\Rpc\\Custom\\")

## Installation

* just add '"basilicom/rpc-gateway": "dev-master"' to your composer '"require": {}'

## API Reference

* n/a

## Tests

* none

## Contributors

* Conrad Guelzow <conrad.guelzow@basilicom.de>

## License

* BSD-3-Clause
