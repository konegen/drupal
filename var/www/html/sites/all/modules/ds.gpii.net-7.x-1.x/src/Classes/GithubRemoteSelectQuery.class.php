	<?php

	/**
	 * @file
	 * Contains the GithubProjectsRemoteSelectQuery class.
	 */

	/**
	 * Select query for our remote data.
	 *
	 * @todo Make vars protected once no longer developing.
	 */
	class GithubRemoteSelectQuery extends RemoteEntityQuery {

	  /**
	   * Determines whether the query is RetrieveMultiple or Retrieve.
	   *
	   * The query is Multiple by default, until an ID condition causes it to be
	   * single.
	   *
	   * @var bool
	   */
	  public $retrieve_multiple = TRUE;

	  /**
   * An array of conditions on the query, grouped by the table they are on.
   *
   * @var array
   */
  public $conditions = array();

  /**
   * The from date filter for event searches.
   *
   * @var object
   */
  public $from_date = NULL;

  /**
   * The to date filter for event searches.
   *
   * @var object
   */
  public $to_date = NULL;

  /**
   * The user id.
   *
   * @var object
   */
  public $user_id = NULL;

  /**
   * Constructor to generically set up the user id condition if there is a user.
   *
   * @param object $connection
   *   Connection used to make REST requests.
   */
  public function __construct($connection) {
    parent::__construct($connection);
  }

  /**
   * Add a condition to the query.
   *
   * Originally based on the entityCondition() method in EntityFieldQuery, but
   * largely from USDARemoteSelectQuery (Programming Drupal 7 Entities) and
   * MSDynamicsSoapSelectQuery.
   *
   * @param string $name
   *   The name of the entity property.
   * @param string $value
   *   The value of the entity property.
   * @param string $operator
   *   The comparison operator.
   */
  public function entityCondition($name, $value, $operator = NULL) {

    // We only support the entity ID for now.
    if ($name == 'entity_id') {

      // Get the remote field name of the entity ID.
      $field = $this->entity_info['remote entity keys']['remote id'];

      // Set the remote ID field to the passed value.
      $this->conditions[$this->remote_base][] = array(
        'field' => $field,
        'value' => $value,
        'operator' => $operator,
      );

      // Record that we'll only be retrieving a single item.
      if (is_null($operator) || ($operator == '=')) {
        $this->retrieve_multiple = FALSE;
      }
    }
    else {

      // Report an invalid entity condition.
      $this->throwException(
        'GITHUBPROJECTSREMOTESELECTQUERY_INVALID_ENTITY_CONDITION',
        'The query object can only accept the \'entity_id\' condition.'
);
      //dpm('The query object can only accept the \'entity_id\'');
    }
  }

  /**
   * Add a condition to the query, using local property keys.
   *
   * Based on MSDynamicsSoapSelectQuery::propertyCondition().
   *
   * @param string $property_name
   *   The name of the property.
   * @param string $value
   *   The value of the property.
   * @param string $operator
   *
   *   A local property. Ie, a key in the $entity_info 'property map' array.
   */
  public function propertyCondition($property_name, $value, $operator = NULL) {

    // Make sure the entity base has been set up.
    if (!isset($this->entity_info)) {
      $this->throwException(
      'GITHUBPROJECTSREMOTESELECTQUERY_ENTITY_BASE_NOT_SET',
      'The query object was not set with an entity type.'
      );
    }

    // Make sure that the provided property is valid.
    if (!isset($this->entity_info['property map'][$property_name])) {
      $this->throwException(
      'GITHUBPROJECTSREMOTESELECTQUERY_INVALID_PROPERY',
      'The query object cannot set a non-existent property.'
      );
    }

    // Adding a field condition (probably) automatically makes this a multiple.
    // TODO: figure this out for sure!
    $this->retrieve_multiple = TRUE;

    // Use the property map to determine the remote field name.
    $remote_field_name = $this->entity_info['property map'][$property_name];

    // Set the condition for use during execution.
    $this->conditions[$this->remote_base][] = array(
      'field' => $remote_field_name,
      'value' => $value,
      'operator' => $operator,
    );
  }

  /**
   * Run the query and return a result.
   *
   * Uses  makeRequest('event?eventId=ID', 'GET');.
   *
   * @return array
   *   Remote entity objects as retrieved from the remote connection.
   */
  public function execute() {

	  $repositories = [];
	  $entities = [];

	  // If there are any validation errors, don't perform a search.
	  if (form_set_error()) {
		  return array();
	  }

	  $path = "users/" . variable_get("github.login", "") . "/starred";


	  //$k = 0;
	  while($path){
		  // Make the request.
		  try {
			  $response = $this->connection->makeRequest($path, 'GET', array('Accept' => 'application/vnd.github.mercy-preview+json'));


		  switch ($this->base_entity_type) {
		  case 'github_remote_repository':
			  watchdog('github', 'Entity Type is github_remote_repository', array(), WATCHDOG_DEBUG);


			  // Fetch the list of events.

			  if ($response->code == 404) {
				  // No data was returned so let's provide an empty list.
				  watchdog('github', 'Response Code 404', array(), WATCHDOG_DEBUG);
				  $repositories = array();
			  }
			  else /* We have response data */ {
				  // Convert the JSON (assuming that's what we're getting) into a PHP array.
				  // Do any unmarshalling to convert the response data into a PHP array.
				  watchdog('github', 'Response data received', array(), WATCHDOG_DEBUG);
				  $ttt =array_values(json_decode($response->data, TRUE));
			  }

			  for ($i= 0; $i<count($ttt); $i++) {
				  $repositories[] = $ttt[$i];
			  }
			  break;
		  }


		  $headers = $response->headers;
		  if (isset($headers['link'])) {
			  $path=github_parseLink($headers['link']);
		  }
		  else
			  $path=NULL;
		  }
		  catch (Exception $e) {
			  drupal_set_message($e->getMessage());
			  $path=NULL;
		  }

	  }
	  //dpm($response);


	  //		print_r("\nbefore:".count($repositories));

	  if (isset($this->conditions[$this->remote_base])) {
		  foreach ($this->conditions[$this->remote_base] as $condition) {
			  switch ($condition['field']) {
			  case 'id':
				  $repository_id = $condition['value'];
				  $repositories = array_filter($repositories, function ($objects) use ($repository_id) {
					  return ($objects["id"] == $repository_id);
				  });
				  break;

			  case 'fullname':
			  case 'full_name':
				  $repository_full_name = $condition['value'];
				  $repositories = array_filter($repositories, function ($objects) use ($repository_full_name) {
					  return ($objects["full_name"] == $repository_full_name);
				  });
				  break;
			  }
		  }
	  }
	  //		print_r("after:\n".count($repositories));
	  // Return the list of results.
	  $entities = $this->parseEventResponse($repositories);
	  //`		print_r("entity\n".count($entities));
	  return $entities;

  }

  /**
   * Helper for execute() which parses the JSON response for event entities.
   *
   * May also set the $total_record_count property on the query, if applicable.
   *
   * @param object $response
   *   The JSON/XML/whatever response from the REST server.
   *
   * @return array
   *   An list of entity objects, keyed numerically.
   *   An empty array is returned if the response contains no entities.
   *
   * @throws Exception
   *   Exception if a fault is received when the REST call was made.
   */
  public function parseEventResponse($repositories) {


	  // Initialize an empty list of entities for returning.
	  $entities = array();

	  // Iterate through each event.
	  foreach ($repositories as $key=>$repository) {
		  watchdog('github', 'Repository no. %key : %name', array('%key' => $key, '%name' => $repository['full_name']), WATCHDOG_DEBUG);

		  $entity = $repository;
/*
		  foreach(github_get_remote_properties()["github_remote_repository"] as $p => $i)
		  {
			  if(isset($repository[$p]))
				  $entity[$p]=$repository[$p];
		  }
 */



		  // Make the request.
		  try {
			  $readmePath = "repos/" . $repository['full_name'] . "/readme";
			  $readmeResponse = $this->connection->makeRequest($readmePath, 'GET', array('Accept' => 'application/vnd.github.v3.html'));
			  $entity["readme"] = $this->parseReadmeResponse($readmeResponse);
		  }
		  catch (Exception $e) {
			  drupal_set_message($e->getMessage());
		  }

		  $licensePath = "repos/" . $repository['full_name'] . "/license";

		  // Make the request.
		  try {
			  $licenseResponse = $this->connection->makeRequest($licensePath, 'GET', array('Accept' => 'application/vnd.github.drax-preview+json'));
			  $entity["license"] = $this->parseLicenseResponse($licenseResponse);
		  }
		  catch (Exception $e) {
			  drupal_set_message($e->getMessage());
		  }
		  $entities[]=(object)$entity;
	  }

	  // Return the newly-created list of entities.
	  return $entities;
  }

  /**
   * Helper for execute() which parses the response for repository readmes.
   *
   * May also set the $total_record_count property on the query, if applicable.
   *
   * @param object $response
   *   The response from the REST server.
   *
   * @return string
   *   Readme string.
   *
   * @throws Exception
   *   Exception if a fault is received when the REST call was made.
   */
  public function parseReadmeResponse($response) {

	  // Fetch the list of events.
	  if ($response->code == 404) {
		  watchdog('github', 'Readme response data not received', array(), WATCHDOG_DEBUG);
		  // No data was returned so let's provide an empty list.
		  $readme = NULL;
	  }
	  else /* We have response data */ {
		  watchdog('github', 'Readme response data received', array(), WATCHDOG_DEBUG);
		  // Convert the JSON (assuming that's what we're getting) into a PHP array.
		  // Do any unmarshalling to convert the response data into a PHP array.
		  $readme = $response->data;
	  }
	  return $readme;
  }

  /**
   * Helper for execute() which parses the response for repository readmes.
   *
   * May also set the $total_record_count property on the query, if applicable.
   *
   * @param object $response
   *   The response from the REST server.
   *
   * @return string
   *   Readme string.
   *
   * @throws Exception
   *   Exception if a fault is received when the REST call was made.
   */
  public function parseLicenseResponse($response) {

	  // Fetch the list of events.
	  if ($response->code == 404) {
		  watchdog('github', 'License response data not received', array(), WATCHDOG_DEBUG);
		  // No data was returned so let's provide an empty list.
		  $license = "undefined";
	  }
	  else /* We have response data */ {
		  watchdog('github', 'License response data received', array(), WATCHDOG_DEBUG);
		  // Convert the JSON (assuming that's what we're getting) into a PHP array.
		  // Do any unmarshalling to convert the response data into a PHP array.
		  $license = json_decode($response->data, TRUE)['license']['name'];
	  }
	  return $license;
  }

  /**
   * Throw an exception when there's a problem.
   *
   * @param string $code
   *   The error code.
   * @param string $message
   *   A user-friendly message describing the problem.
   *
   * @throws Exception
   */
  public function throwException($code, $message) {

	  // Report error to the logs.
	  watchdog('github', 'ERROR: GithubProjectsRemoteSelectQuery: "@code", "@message".', array(
		  '@code' => $code,
		  '@message' => $message,
	  ));

	  // Throw an error with which callers must deal.
	  throw new Exception(t("GithubProjectsRemoteSelectQuery error, got message '@message'.", array(
		  '@message' => $message,
	  )), $code);
  }

  /**
   * Build the query from an EntityFieldQuery object.
   *
   * To have our query work with Views using the EntityFieldQuery Views module,
   * which assumes EntityFieldQuery query objects, it's necessary to convert
   * from the EFQ so that we may execute this one instead.
   *
   * @param object $efq
   *   The built-up EntityFieldQuery object.
   *
   * @return object
   *   The current object.  Helpful for chaining methods.
   */
  public function buildFromEFQ($efq) {

    // Copy all of the conditions.
    foreach ($efq->propertyConditions as $condition) {

      // Handle various conditions in different ways.
      switch ($condition['column']) {

        // Get the from date.
        case 'from_date':
          $from_date = $condition['value'];
          // Convert the date to the correct format for the REST service.
          $result = $from_date->format('Y/m/d');
          // The above format() can return FALSE in some cases, so add a check.
          if ($result) {
            $this->from_date = $result;
          }
          break;

        // Get the to date.
        case 'to_date':
          $to_date = $condition['value'];
          // Convert the date to the correct format for the REST service.
          $result = $to_date->format('Y/m/d');
          // The above format() can return FALSE in some cases, so add a check.
          if ($result) {
            $this->to_date = $result;
          }
          break;

        // Get the user ID.
        case 'user_id':
          $this->user_id = $condition['value'];
          break;

        default:
          $this->conditions[$this->remote_base][] = array(
            'field' => $condition['column'],
            'value' => $condition['value'],
            'operator' => isset($condition['operator']) ? $condition['operator'] : NULL,
          );
          break;
      }
    }

    return $this;
  }

}
