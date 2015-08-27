<?php

namespace Aw\Common\Cache;

use Doctrine\Common\Cache\CacheProvider;

/**
 * MySql DB cache provider.
 *
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
    
    private $cm;

    public function __construct($cm, $table)
    {
        $this->cm = $cm;
        $this->table = $table;
    }

    /**
     * {@inheritdoc}
     */
    protected function doFetch($id)
    {
        //$document = $this->collection->findOne(array('_id' => $id), array(self::DATA_FIELD, self::EXPIRATION_FIELD));
        $stmt = $this->cm->prepare("SELECT `" . self::DATA_FIELD . "`, `" . self::EXPIRATION_FIELD . "` FROM `" . $this->table . "` WHERE `id` = :id");
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
        
        $stmt = $this->cm->prepare("SELECT `id`, `" . self::DATA_FIELD . "`, `" . self::EXPIRATION_FIELD . "` FROM `" . $this->table . "` WHERE `id` IN ('" . implode("', '", $keys) . "')");
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
        $stmt = $this->cm->prepare("SELECT `" . self::EXPIRATION_FIELD . "` FROM `" . $this->table . "` WHERE `id` = :id");
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
        $stmt = $this->cm->prepare("
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
        $stmt = $this->cm->prepare("DELETE FROM `" . $this->table . "` WHERE `id` = :id");
        $stmt->bindValue('id', $id);
        $result = $stmt->execute();

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function doFlush()
    {
        $stmt = $this->cm->prepare("TRUNCATE TABLE `" . $this->table . "`");
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
        /*
        $serverStatus = $this->collection->db->command(array(
            'serverStatus' => 1,
            'locks' => 0,
            'metrics' => 0,
            'recordStats' => 0,
            'repl' => 0,
        ));

        $collStats = $this->collection->db->command(array('collStats' => 1));

        return array(
            Cache::STATS_HITS => null,
            Cache::STATS_MISSES => null,
            Cache::STATS_UPTIME => (isset($serverStatus['uptime']) ? (integer) $serverStatus['uptime'] : null),
            Cache::STATS_MEMORY_USAGE => (isset($collStats['size']) ? (integer) $collStats['size'] : null),
            Cache::STATS_MEMORY_AVAILABLE  => null,
        );
        */
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
