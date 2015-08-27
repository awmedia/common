<?php

namespace Aw\Common\Spl\Iterators;

use \Iterator,
    \Exception;

/**
 * CSV Iterator
 * @author Jon LaBelle
 * @author Jerry Sietsma
 */
class CsvIterator implements Iterator
{
    /**
     * Must be greater than the longest line (in characters) to be found in
     * the CSV file (allowing for trailing line-end characters).
     *
     * @var int
     */
    const ROW_LENGTH = 4048;

    /**
     * Resource file pointer
     */
    private $_filePointer;

    /**
     * Represents current element in iteration
     *
     * @var int
     */
    private $_currentElement;

    /**
     * Cumalitve row count of CSV data
     *
     * @var int
     */
    private $_rowCounter;

    /**
     * CSV column delimeter
     *
     * @var string
     */
    private $_delimiter;
    
    private $_enclosure;
    
    private $_escape;
    
    private $_hasHeaderWithColumns;
    
    private $_ignoreHeaderWithColumns;
    
    private $_useHeaderColumnsAsIndex;
    
    private $_ignoreEmptyRows;

    /**
     * Create an instance of the CsvIterator class.
     *
     * @param string $file The CSV file path or a file handle.
     * @param string $delimiter The default delimeter is a single comma (,)
     */
    public function __construct($file, $delimiter = ',', $enclosure = '"', $escape = '\\')
    {
        $this->_initializedWithHandle = !is_string($file) && get_resource_type($file) !== false;
        $this->_filePointer =  $this->_initializedWithHandle ? $file : fopen($file, 'rt');
        $this->_delimiter = $delimiter;
        $this->_enclosure = $enclosure;
        $this->_escape = $escape;

        $this->_hasHeaderWithColumns = true;
        $this->_ignoreHeaderWithColumns = true;
        $this->_useHeaderColumnsAsIndex = true;
        $this->_ignoreEmptyRows = true;
        $this->_columns = null;
        
        // init: set columns if available
        $this->rewind();
    }

    /*
     * @see Iterator::rewind()
     */
    public function rewind()
    {
        $this->_rowCounter = 0;
        rewind($this->_filePointer);
        $this->next();

        if ($this->_hasHeaderWithColumns === true)
        {
            $this->_columns = $this->current();
            $this->_columnCount = count($this->_columns);
            
            if ($this->_ignoreHeaderWithColumns === true)
            {
                $this->next();
            }
        }
    }

    /*
     * @see Iterator::current()
     */
    public function current()
    {
        return $this->_currentElement;
    }

    /*
     * @see Iterator::key()
     */
    public function key()
    {
        return $this->_rowCounter;
    }

    /*
     * @see Iterator::next()
     */
    public function next()
    {
        $hasNext = !feof($this->_filePointer);
        $this->_currentElement = null;
        
        if ($hasNext)
        {
            $data = fgetcsv($this->_filePointer, self::ROW_LENGTH, $this->_delimiter, $this->_enclosure, $this->_escape);
    
            if ($data !== false)
            {
                if ($this->_ignoreEmptyRows)
                {
                    $dataTmp = $data;  
                        
                    $dataTmp = array_filter($dataTmp);
                    
                    if (empty($dataTmp))
                    {
                        if ($this->next())
                        {
                            return $this->next();
                        }
                    }
                }
                
                if ($data)
                {
                    $this->_currentElement = $data;
                    
                    if ($this->_useHeaderColumnsAsIndex && ($this->_hasHeaderWithColumns && $this->_rowCounter > 0))
                    {
                          $this->_currentElement = @array_combine($this->_columns, $data);
                          
                          if ($this->_currentElement === false)
                          {
                              if (count($data) !== $this->_columnCount)
                              {
                                  /*
                                  echo '<pre>';
                                  echo '<h1>Headers:</h1>';
                                  print_r($this->_columns);
                                  echo '<h1>Data:</h1>';
                                  print_r($data);
                                  echo '</pre>';
                                  */
                                  //exit;
                                  
                                  throw new Exception('The number of headers don\'t match the number of columns');
                              }
                          }
                    }
                    
                    $this->_rowCounter ++;
                }
            }
            else
            {
                $this->_currentElement = null;
            }
        }
        
        return $this->_currentElement;
    }

    /*
     * @see Iterator::valid()
     */
    public function valid()
    {
        if ($this->_currentElement === null && feof($this->_filePointer))
        {
            if (!$this->_initializedWithHandle)
            {
                fclose($this->_filePointer);
            }
            
            return false;
        }
        return true;
        
        /*
        if (!$this->next())
        {
            if (!$this->_initializedWithHandle)
            {
                fclose($this->_filePointer);
            }
            
            return false;
        }
        
        return true;
        */
    }
    
    /**
     * Public methods
     */
    
    public function setHasHeaderWithColumns($hasHeaderWithColumns)
    {
        $this->_hasHeaderWithColumns = $hasHeaderWithColumns;
        $this->rewind();
        return $this;
    }
    
    public function setIgnoreHeaderWithColumns($ignore)
    {
        $this->_ignoreHeaderWithColumns = $ignore;
        $this->rewind();
        return $this;
    }
    
    public function setUseHeaderColumnsAsIndex($useHeaderColumnsAsIndex)
    {
        $this->_useHeaderColumnsAsIndex = (bool) $useHeaderColumnsAsIndex;
        $this->rewind();
        return $this;
    }
    
    public function setIgnoreEmptyRows($ignore)
    {
        $this->ignoreEmptyRows = $ignore;
        return $this;
    }
    
    public function columns()
    {
        return $this->_columns;
    }
}