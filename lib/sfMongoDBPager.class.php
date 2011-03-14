<?php

class sfMongoDBPager extends sfPager
{
  protected $query = array();

  /**
   * A reference to the MongoCollection to retrieve results from.
   *
   * @var MongoCollection
   */
  protected $collection = null;

  /**
   * A reference to the MongoCursor of the result set.
   *
   * @var MongoCursor
   */
  protected $mongoCursor = null;

  /**
   * Constructor.
   *
   * @param string  $namespace  The namespace of this pager.
   * @param integer $maxPerPage Number of records to display per page
   */
  public function __construct($namespace, $maxPerPage = 10)
  {
    parent::__construct($namespace, $maxPerPage);
  }

  /**
   * Sets the MongoDB collection to operate on.
   *
   * @param MongoCollection $collection
   *
   * @return sfMongoDBPager $this
   */
  public function setCollection(MongoCollection $collection)
  {
    $this->collection = $collection;

    return $this;
  }

  /**
   * Returns the MongoCollection.
   *
   * @return MongoCollection
   */
  public function getCollection()
  {
    return $this->collection;
  }

  /**
   * Sets the MongoDB query used on the find operation.
   *
   * @param array $query
   *
   * @return sfMongoDBPager $this
   */
  public function setQuery(array $query)
  {
    $this->query = $query;

    return $this;
  }

  /**
   * Returns the query used by this pager.
   *
   * @return array
   */
  public function getQuery()
  {
    return $this->query;
  }

  /**
   * (non-PHPdoc)
   * @see sfPager::getResults()
   */
  public function getResults()
  {
    $results = $this->getCollection()->find($this->getQuery());

    $params = array(
    	'limit',
    	'skip',
    	'sort',
    );

    foreach ($params as $eachParameter)
    {
      if ($param = $this->getParameter($eachParameter, false))
      {
        $results->$eachParameter($param);
      }
    }

    return $results;
  }

  /**
   * Initializes the pager by retrieving basic information from the MongoDB.
   *
   * @throws sfInitializationException
	 *
	 * @return sfMongoDBPager $this
   */
  public function init()
  {
    if (empty($this->collection))
    {
      throw new sfInitializationException('There is no MongoCollection set for this pager.');
    }

    $hasMaxRecordLimit = ($this->getMaxRecordLimit() !== false);
    $maxRecordLimit = $this->getMaxRecordLimit();

    $count = $this->getCollection()->count($this->getQuery());

    $this->setNbResults($hasMaxRecordLimit ? min($count, $maxRecordLimit) : $count);

    if ($this->getPage() == 0 or $this->getMaxPerPage() == 0)
    {
      $this->setLastPage(0);
    }
    else
    {
      $this->setLastPage(ceil($this->getNbResults() / $this->getMaxPerPage()));

      $offset = ($this->getPage() - 1) * $this->getMaxPerPage();
      $this->setParameter('skip', $offset);

      if ($hasMaxRecordLimit)
      {
        $maxRecordLimit = $maxRecordLimit - $offset;
        if ($maxRecordLimit > $this->getMaxPerPage())
        {
          $this->setParameter('limit', $this->getMaxPerPage());
        }
        else
        {
          $this->setParameter('limit', $maxRecordLimit);
        }
      }
      else
      {
        $this->setParameter('limit', $this->getMaxPerPage());
      }
    }

    return $this;
  }

  /**
   * (non-PHPdoc)
   * @see sfPager::retrieveObject()
   */
  protected function retrieveObject($offset)
  {
    return $this->getResults()->limit(1)->skip($offset);
  }
}