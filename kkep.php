<?php

namespace KKEP {
    class KKEP_Calculate_Price_Request
    {
        function __construct()
        {
            $this->products = array();
        }

        /**
         * @var string $countryCode
         */
        public $countryCode;

        /**
         * @var string $currency 
         */
        public $currency;

        /**
         * @var integer $volumeMultiplier 
         */
        public $volumeMultiplier;

        /**
         * @var array<KKEP_Product> $packageProp 
         */
        public $products;
    }

    class KKEP_Product
    {
        /**
         * @var float $height
         */
        public $height;

        /**
         * @var float $width
         */
        public $width;

        /**
         * @var float $length
         */
        public $length;

        /**
         * @var float $weight
         */
        public $weight;

        /**
         * @var int $quantity
         */
        public $quantity;
    }

    class KKEP_Address
    {

        /**
         * @var string $name 
         */
        public $name;

        /**
         * @var string $contactName 
         */
        public $contactName;

        /**
         * @var string $street1 
         */
        public $street1;

        /**
         * @var string $street2 
         */
        public $street2;

        /**
         * @var string $postCode 
         */
        public $postcode;

        /**
         * @var string $city 
         */
        public $city;

        /**
         * @var string $province 
         */
        public $province;

        /**
         * @var string $country 
         */
        public $country;

        /**
         * @var string $telephone 
         */
        public $telephone;

        /**
         * @var string $email 
         */
        public $email;

        /**
         * @var string $vatno 
         */
        public $vatno;
    }

    class KKEP_SendOrderRequest
    {
        function __construct()
        {
            $this->products = array();
        }
        /**
         * @var string $orderId 
         */
        public $orderId;

        /**
         * @var int $origin 
         */
        public $origin;

        /**
         * @var string $currency 
         */
        public $currency;

        /**
         * @var KKEP_Address $receiver 
         */
        public $receiver;

        /**
         * @var array<KKEP_Order_Product> $products 
         */
        public $products;
    }

    class KKEP_Order_Product
    {
        /**
         * @var int $quantity 
         */
        public $quantity;

        /**
         * @var string $description 
         */
        public $description;

        /**
         * @var float $weight 
         */
        public $weight;

        /**
         * @var float $unitValue 
         */
        public $unitValue;

        /**
         * @var float $value 
         */
        public $value;

        /**
         * @var string $hts 
         */
        public $hts;

        /**
         * @var string $country 
         */
        public $country;
    }
}
