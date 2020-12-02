<?php

namespace Tuf\Metadata;

use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\Optional;
use Symfony\Component\Validator\Constraints\Required;
use Symfony\Component\Validator\Constraints\Type;
use Tuf\Exception\MetadataException;
use Tuf\JsonNormalizer;
use Tuf\SignatureVerifier;
use function DeepCopy\deep_copy;

class RootMetadata extends MetadataBase
{
    private static $flag = FALSE;

    /**
     * {@inheritdoc}
     */
    protected const TYPE = 'root';

    public static function createFromJsonUsingSelfVerfication(string $json)
    {
        /**
         * change this to override createFromJson but make $verifier optional
         * use a static flage so that you can only call the method once without
         * the verifier
         */

        // ☹️ This is why I don't think this method is better. For root you have to
        // validate before anys to be able to get the roles and keys to check
        // the signature. This would be true even if we didn't have the SignatureVerifier
        // class.
        $data = JsonNormalizer::decode($json);
        static::validateMetaData($data);
        $rootMetadata = new static($data);
        $verifier = SignatureVerifier::createFromRootMetadata($rootMetadata);
        return parent::createFromJson($json, $verifier);
    }




    /**
     * {@inheritdoc}
     */
    protected static function getSignedCollectionOptions(): array
    {
        $options = parent::getSignedCollectionOptions();
        $options['fields']['keys'] = new Required([
            new Type('\ArrayObject'),
            new Count(['min' => 1]),
            new All([
                static::getKeyConstraints(),
            ]),
        ]);
        $roleConstraints = new Collection(
            self::getKeyidsConstraints() +
            static::getThresholdConstraints()
        );
        $options['fields']['roles'] = new Collection([
            'targets' => new Required($roleConstraints),
            'timestamp' => new Required($roleConstraints),
            'snapshot' => new Required($roleConstraints),
            'root' => new Required($roleConstraints),
            'mirror' => new Optional($roleConstraints),
        ]);
        $options['fields']['consistent_snapshot'] = new Required([
            new Type('boolean'),
        ]);
        return $options;
    }

    /**
     * Gets the roles from the metadata.
     *
     * @return \ArrayObject
     *   An ArrayObject where the keys are role names and the values arrays with the
     *   following keys:
     *   - keyids (string[]): The key ids.
     *   - threshold (int): Determines how many how may keys are need from
     *     this role for signing.
     */
    public function getRoles():\ArrayObject
    {
        return deep_copy($this->getSigned()['roles']);
    }

    /**
     * Gets the keys for the root metadata.
     *
     * @return \ArrayObject
     *   An ArrayObject of keys information where the array keys are the key ids and
     *   the values are arrays with the following values:
     *   - keyid_hash_algorithms (string[]): The key id algorithms used.
     *   - keytype (string): The key type.
     *   - keyval (string[]): A single item array where key value is 'public'
     *     and the value is the public key.
     *   - scheme (string): The key scheme.
     */
    public function getKeys():\ArrayObject
    {
        return deep_copy($this->getSigned()['keys']);
    }

    /**
     * Determines whether consistent snapshots are supported.
     *
     * @return boolean
     *   Whether consistent snapshots are supported.
     */
    public function supportsConsistentSnapshots() : bool
    {
        return $this->getSigned()['consistent_snapshot'];
    }
}
