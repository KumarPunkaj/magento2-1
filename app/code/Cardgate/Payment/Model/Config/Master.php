<?php
/**
 * Copyright © 2016 CardGate.
 * All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Cardgate\Payment\Model\Config;

use Magento\Framework\App\ObjectManager;

/**
 *
 * @author DBS B.V.
 * @package Magento2
 */
class Master {

	/**
	 *
	 * @var array
	 */
	private $paymentMethodIds = [];

	/**
	 *
	 * @var array
	 */
	private $paymentMethodCodes = [];

	/**
	 *
	 * @var \Magento\Framework\App\Cache\Type\Collection
	 */
	private $cache;

	const CACHEKEY = "cgAllPM";

	/**
	 *
	 * @var \Cardgate\Payment\Model\Config
	 */
	private $config;

	public function __construct ( \Magento\Framework\App\Cache\Type\Collection $cache ) {
		$this->cache = $cache;
		if ( $this->cache->test( self::CACHEKEY ) !== false ) {
			try {
				$cachedPMs = unserialize( $this->cache->load( self::CACHEKEY ) );
			} catch ( \Exception $e ) {
				$cachedPMs = [];
			}
			$this->paymentMethodIds = $cachedPMs;
		}
		if ( !is_array( $this->paymentMethodIds ) || count( $this->paymentMethodIds ) < 1 ) {
			$this->updatePaymentMethods();
		}

		$this->paymentMethodCodes = [];
		foreach ( $this->paymentMethodIds as $paymentMethod => $paymentMethodName ) {
			$this->paymentMethodCodes["cardgate_{$paymentMethod}"] = $paymentMethodName;
			$this->ensurePaymentClass( "cardgate_{$paymentMethod}" );
		}
	}

	/**
	 * Set CardGate config
	 *
	 * @param \Cardgate\Payment\Model\Config $config
	 */
	public function setConfig ( \Cardgate\Payment\Model\Config $config ) {
		$this->config = $config;
	}

	/**
	 * Get Paymentmethod classname by Code
	 *
	 * @param string $paymentMethodCode
	 * @param string $fullClassName
	 * @return string
	 */
	public function getPMClassByCode ( $paymentMethodCode, $fullClassName = true ) {
		return ( $fullClassName ? 'Cardgate\\Payment\\Model\\PaymentMethod\\' : '' ) . substr( $paymentMethodCode, 9 );
	}

	/**
	 * Get Paymentmethod instance by Code.
	 * $force can be set to ensure class exists (or create it if not exists)
	 *
	 * @param string $paymentMethodCode
	 * @param string $force
	 * @return \Cardgate\Payment\Model\PaymentMethods
	 */
	public function getPMInstanceByCode ( $paymentMethodCode, $force = false ) {
		if ( $force ) {
			$this->ensurePaymentClass( $paymentMethodCode );
		}
		return ObjectManager::getInstance()->get( $this->getPMClassByCode( $paymentMethodCode ) );
	}

	/**
	 * Test if Paymentmethod ID exists (CardGate style ID)
	 *
	 * @param string $paymentMethodId
	 * @return boolean
	 */
	public function hasPMId ( $paymentMethodId ) {
		return isset( $this->paymentMethodIds[$paymentMethodId] );
	}

	/**
	 * Tests if Paymentmethod Code is a CardGate Code
	 *
	 * @param unknown $paymentMethodCode
	 * @return boolean
	 */
	public function isCardgateCode ( $paymentMethodCode ) {
		return ( substr( $paymentMethodCode, 0, 9 ) == 'cardgate_' );
	}

	/**
	 * Get Paymentmethod Code for a given PM ID (CardGate style ID)
	 *
	 * @param unknown $paymentMethodId
	 * @return string
	 */
	public function getPMCodeById ( $paymentMethodId ) {
		return 'cardgate_' . $paymentMethodId;
	}

	/**
	 * Create Paymentmethod Class if not exists.
	 *
	 * @param unknown $paymentMethodCode
	 * @return void
	 */
	private function ensurePaymentClass ( $paymentMethodCode ) {
		if ( ! \class_exists( $this->getPMClassByCode( $paymentMethodCode ) ) ) {
			eval( "namespace Cardgate\\Payment\\Model\\PaymentMethod; class " . $this->getPMClassByCode( $paymentMethodCode, false ) . " extends \\Cardgate\\Payment\\Model\\PaymentMethod\\nonexistent {}" );
		}
	}

	/**
	 * Load Paymentmethods and save them in cache
	 *
	 * @return void
	 */
	private function updatePaymentMethods () {
		// YYY: Make dynamic
		$this->paymentMethodIds = [
			'ideal' => 'iDeal',
			'mistercash' => 'Bancontact',
			'creditcard' => 'Creditcard',
			'paysafecard' => 'PaySafeCard',
			'sofortbanking' => 'SofortBanking',
			'paypal' => 'PayPal',
			'klarna' => 'Klarna',
			'clickandbuy' => 'ClickandBuy',
			'afterpay' => 'Afterpay',
			'directdebit' => 'DirectDebit',
			'przelewy24' => 'Przelewy24',
			'focum' => 'Focum',
			'wallet' => 'Wallet',
			'safeklick' => 'Safeklick',
			'banktransfer' => 'Bank transfer',
			'giropay' => 'Giropay',
			'pos' => 'POS',
			'capayable' => 'Capayable',
			'bitcoin' => 'Bitcoin',
			'belfius' => 'Belfius',
			'eps' => 'EPS'
		];
		$this->cache->save( serialize( $this->paymentMethodIds ), self::CACHEKEY, [], 24 * 3600 );
	}

	public function getCardgateMethods () {
		return $this->paymentMethodIds;
	}

	public function getPaymentMethods ( $bIncludingNames = false ) {
		if ( $bIncludingNames ) {
			return $this->paymentMethodCodes;
		} else {
			return array_keys( $this->paymentMethodCodes );
		}
	}

}