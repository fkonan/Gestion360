<?php

namespace App\Hashing;

use Illuminate\Contracts\Hashing\Hasher;

class SHA1HashDriver implements Hasher
{
   /**
    * Hash the given value.
    */
   public function make($value, array $options = []): string
   {
      // Convertir a ISO-8859-1 igual que en Java
      $isoString = mb_convert_encoding($value, 'ISO-8859-1', 'UTF-8');
      return sha1($isoString);
   }

   /**
    * Check the given plain value against a hash.
    */
   public function check($value, $hashedValue, array $options = []): bool
   {
      return hash_equals($hashedValue, $this->make($value, $options));
   }

   /**
    * Check if the given hash has been hashed using the given options.
    */
   public function needsRehash($hashedValue, array $options = []): bool
   {
      // SHA-1 es considerado débil, podrías retornar true para forzar rehash
      return false;
   }

   /**
    * Get information about the given hashed value.
    */
   public function info($hashedValue): array
   {
      return [
         'algo' => 'sha1',
         'algoName' => 'sha1',
      ];
   }
}
