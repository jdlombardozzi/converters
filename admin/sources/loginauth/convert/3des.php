<?php
class tripleDES {
	var $publicKey = null; // PUBLIC
	var $privateIv = null; // PRIVATE
	var $kToken = null;

  public function __construct($values=array()) {
    if ( isset($values['public_key']) ) {
      $this->publicKey = $values['public_key'];
    }

    if ( isset($values['decryptionKey']) ) {
      $this->publicKey = $this->hexstr($values['decryptionKey']);
    }

    if ( isset($values['private_iv']) ) {
      $this->privateIv = $values['private_iv'];
    }

    if ( isset($values['k_token']) ) {
      $this->kToken = $values['k_token'];
    }
  }

	function fetchKValue() {
		return $this->encryptToken( $this->kToken );
	}

	function encryptToken( $token ) {
		return base64_encode( $this->encryptNET3DES( $this->publicKey, $this->privateIv, $token ) );
	}

	function decryptToken( $token ) {
		return $this->decryptNET3DES( $this->publicKey, $this->privateIv, base64_decode($token) );
	}

	function decryptNET3DES($key, $vector, $text) {
		$td = mcrypt_module_open (MCRYPT_3DES, '', MCRYPT_MODE_CBC , ''); //CBC is the default mode in .NET

		// Complete the key
		$key_add = 24-strlen($key);
		$key .= substr($key,0,$key_add);

		//Padding the text
		$text_add = strlen($text)%8;
		for($i=$text_add; $i<8; $i++){
			$text .= chr(8-$text_add);
		}

		@mcrypt_generic_init ($td, $key, $vector);
		$decrypt64 = mdecrypt_generic ($td, $text);
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);

		// Remove padding
		$block = mcrypt_get_block_size('tripledes', 'cbc');
		$decrypt64 = substr($decrypt64,0,strlen($decrypt64) - $block);
		$packing = ord($decrypt64{strlen($decrypt64) - 1});

		$decrypt64 = substr($decrypt64,0,strlen($decrypt64) - $packing);

		// Return the encrypt text in 64 bits code
		return $decrypt64;
	}

	function encryptNET3DES($key, $vector, $text){
		$td = mcrypt_module_open (MCRYPT_3DES, '', MCRYPT_MODE_CBC, ''); //CBC is the default mode in .NET

		// Complete the key
		$key_add = 24-strlen($key);
		$key .= substr($key,0,$key_add);

		// Padding the text
		$text_add = strlen($text)%8;
		for($i=$text_add; $i<8; $i++){
			$text .= chr(8-$text_add);
		}

		@mcrypt_generic_init ($td, $key, $vector);
		$encrypt64 = mcrypt_generic ($td, $text);
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);

		// Return the encrypt text in 64 bits code
		return $encrypt64;
	}

  // Send the machineKey decryptionKey and then use as the public key
  function hexstr($hexstr) {
    $hexstr = str_replace(' ', '', $hexstr);
    $hexstr = str_replace('\x', '', $hexstr);
    $retstr = pack('H*', $hexstr);
    return $retstr;
  }
}