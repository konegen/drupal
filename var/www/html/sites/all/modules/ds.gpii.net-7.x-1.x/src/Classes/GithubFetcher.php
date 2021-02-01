<?php

/**
 * @file
 * Home of the FeedsHTTPFetcherAA and related classes.
 */

/**
 * Result of FeedsHTTPFetcherAAResult::fetch().
 */
class GithubFetcherResult extends FeedsFetcherResult {

  /**
   * Constructor.
   */
  public function __construct($url = NULL, $auth_token = array(), $accept_invalid_cert = FALSE, $source = NULL) {
    parent::__construct('');
  }

  /**
   * Overrides FeedsFetcherResult::getRaw().
   */
  public function getRaw() {
	  $resource = clients_resource_get_for_component('remote_entity', 'github_remote_repository');
	  $query = $resource->getRemoteEntityQuery('select');
	  $query->base('github_remote_repository');
	  $result=$query->execute();
	 // dpm(count($result), "entities returned");
	  return(json_encode($result));
  }
}

/**
 * Fetches data via HTTP.
 */
class GithubFetcher extends FeedsFetcher {

	/**
	 * Implements FeedsFetcher::fetch().
	 */
	public function fetch(FeedsSource $source) {
		return new GithubFetcherResult();
	}

	/**
	 * Clear caches.
	 */
	public function clear(FeedsSource $source) {
	}

	/**
	 * Implements FeedsFetcher::request().
	 */
	public function request($feed_nid = 0) {
		feeds_dbg($_GET);
		@feeds_dbg(file_get_contents('php://input'));
		try {
			feeds_source($this->id, $feed_nid)->existing()->import();
		}
		catch (Exception $e) {
			// In case of an error, respond with a 503.
			drupal_add_http_header('Status', '503 Service unavailable');
			drupal_exit();
		}

		// Will generate the default 200 response.
		drupal_add_http_header('Status', '200 OK');
		drupal_exit();
	}

	/**
	 * Override parent::configDefaults().
	 */
	public function configDefaults() {
		return array();
	}

	/**
	 * Override parent::configForm().
	 */
	public function configForm(&$form_state) {
		$form = array();
		return $form;
	}

	/**
	 * Expose source form.
	 */
	public function sourceForm($source_config) {
		$form = array();
		return $form;
	}

	/**
	 * Override parent::sourceFormValidate().
	 */
	public function sourceFormValidate(&$values) {
	}
}
