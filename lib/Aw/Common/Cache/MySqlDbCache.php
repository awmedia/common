<?php

namespace Aw\Common\Cache;

use Doctrine\Common\Cache\CacheProvider;

/**
 * MySql DB cache provider.
 * @author Jerry Sietsma <jerry@adworksmedia.nl>
 */
class MySqlDbCache extends CacheProvider
{
    /**
     * The data field will store the serialized PHP value.
     */
    const DATA_FIELD = 'd';

    /**
     * The expiration field will store a DateTime value indicating when the
     * cache entry should expire.
     */
    const EXPIRATION_FIELD = 'e';

    /**
     * @var table
     */
    private $table;
    
    /**
     * @var PDO
     */
    private $pdo;

    public function __construct($pdo, $table)
    {
        $this->pdo = $pdo;
        $this->table = $table;
    }

    /**
     * {@inheritdoc}
     */
    protected function doFetch($id)
    {
        $stmt = $this->pdo->prepare("SELECT `" . self::DATA_FIELD . "`, `" . self::EXPIRATION_FIELD . "` FROM `" . $this->table . "` WHERE `id` = :id");
        $stmt->bindValue('id', $id);
        $stmt->execute();
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        
        if ($result === false) {
            return false;
        }

        if ($this->isExpired($result)) {
            $this->doDelete($id);
            return false;
        }

        return unserialize($result[self::DATA_FIELD]);
    }
    
    /**
     * {@inheritdoc}
     */
    protected function doFetchMultiple(array $keys)
    {
        $returnValues = array();
        
        $stmt = $this->pdo->prepare("SELECT `id`, `" . self::DATA_FIELD . "`, `" . self::EXPIRATION_FIELD . "` FROM `" . $this->table . "` WHERE `id` IN ('" . implode("', '", $keys) . "')");
        $stmt->execute();
        $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        if ($items !== false)
        {
            foreach ($items as $item)
            {
                $value = false;
                
                if ($this->isExpired($item)) {
                    $this->doDelete($item['id']);
                    $item = null;
                }
                else
                {
                    $value = unserialize($item[self::DATA_FIELD]);
                }
                
                $returnValues[$item['id']] = $value;
            }
        }

        return $returnValues;
    }

    /**
     * {@inheritdoc}
     */
    protected function doContains($id)
    {
        $stmt = $this->pdo->prepare("SELECT `" . self::EXPIRATION_FIELD . "` FROM `" . $this->table . "` WHERE `id` = :id");
        $stmt->bindValue('id', $id);
        $stmt->execute();
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if ($result === null) {
            return false;
        }

        if ($this->isExpired($result)) {
            $this->doDelete($id);
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function doSave($id, $data, $lifeTime = 0)
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO `" . $this->table . "` (`id`, `" . self::EXPIRATION_FIELD . "`, `" . self::DATA_FIELD . "`) VALUES (:id, :expiration, :data)
            ON DUPLICATE KEY UPDATE `" . self::EXPIRATION_FIELD . "` = :expiration, `" . self::DATA_FIELD . "` = :data
        ");
        $stmt->bindValue('id', $id);
        $stmt->bindValue('expiration', ($lifeTime > 0 ? time() + $lifeTime : null));
        $stmt->bindValue('data', serialize($data));
        $result = $stmt->execute();
        
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function doDelete($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM `" . $this->table . "` WHERE `id` = :id");
        $stmt->bindValue('id', $id);
        $result = $stmt->execute();

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function doFlush()
    {
        $stmt = $this->pdo->prepare("TRUNCATE TABLE `" . $this->table . "`");
        $result = $stmt->execute();
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function doGetStats()
    {
        return array(
            Cache::STATS_HITS => null,
            Cache::STATS_MISSES => null,
            Cache::STATS_UPTIME => null,
            Cache::STATS_MEMORY_USAGE => null,
            Cache::STATS_MEMORY_AVAILABLE  => null
        );
    }

    /**
     * Check if the document is expired.
     *
     * @param array $document
     * @return boolean
     */
    private function isExpired(array $row)
    {
        return isset($row[self::EXPIRATION_FIELD]) &&
            $row[self::EXPIRATION_FIELD] < time();
    }
}
