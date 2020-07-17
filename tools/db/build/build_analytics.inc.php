<?php
  require_once __DIR__ . '/../../base.inc.php';
  require_once(__DIR__ . '/common.inc.php');

  //
  // Collects downloads over last 30 days for all keyboards from Google Analytics
  // We use this data to push more popular keyboards up the search results
  //

  class build_analytics_sql extends build_common {

    // Use the developers console and download your service account
    // credentials in JSON format. Place them in this directory or
    // change the key file location if necessary.

    // This file is stored in the parent directory of the site, so it
    // is not world-readable.
    const KEY_FILE_LOCATION = __DIR__ . '/../../../../google_analytics_key_file.json';

    function execute($data_root) {
      // Load the Google API PHP Client Library.

      if(!empty($this->DBDataSources->mockAnalyticsSqlFile) && file_Exists($this->DBDataSources->mockAnalyticsSqlFile)) {
        copy($this->DBDataSources->mockAnalyticsSqlFile, $data_root . "analytics.sql");
        return true;
      }

      $this->generateKeyFile();

      if(file_exists(build_analytics_sql::KEY_FILE_LOCATION)) {
        $analytics = $this->initializeAnalytics();
        $response = $this->getReport($analytics);
        $sql = $this->generateSQL($response);

        if($sql !== NULL) {
          file_put_contents($data_root . "analytics.sql", $sql);
        }
      }

      return true;
    }

    function generateKeyFile() {
      global $analytics_keyfile_data;
      if(isset($analytics_keyfile_data)) {
        $data = json_decode($analytics_keyfile_data);
      }
      else if(isset($_SERVER['api_keyman_com_analytics'])) {
        $data = json_decode($_SERVER['api_keyman_com_analytics']);
      } else {
        return false;
      }
      $keyFile = [
        "type" => "service_account",
        "project_id" => $data->project_id,
        "private_key_id" => $data->private_key_id,
        "private_key" => $data->private_key,
        "client_email" => $data->client_email,
        "client_id" => $data->client_id,
        "auth_uri" => "https://accounts.google.com/o/oauth2/auth",
        "token_uri" => "https://oauth2.googleapis.com/token",
        "auth_provider_x509_cert_url" => "https://www.googleapis.com/oauth2/v1/certs",
        "client_x509_cert_url" => $data->client_x509_cert_url
      ];
      file_put_contents(build_analytics_sql::KEY_FILE_LOCATION, json_encode($keyFile));
      return true;
    }

    /**
     * Initializes an Analytics Reporting API V4 service object.
     *
     * @return An authorized Analytics Reporting API V4 service object.
     */
    function initializeAnalytics()
    {
      // Create and configure a new client object.
      $client = new Google_Client();
      $client->setApplicationName("Keyboard Download Count Reporting");
      $client->setAuthConfig(build_analytics_sql::KEY_FILE_LOCATION);
      $client->setScopes(['https://www.googleapis.com/auth/analytics.readonly']);
      $analytics = new Google_Service_AnalyticsReporting($client);

      return $analytics;
    }


    /**
     * Queries the Analytics Reporting API V4.
     *
     * @param service An authorized Analytics Reporting API V4 service object.
     * @return The Analytics Reporting API V4 response.
     */
    function getReport($analytics) {

      // Replace with your view ID, for example XXXX.
      $VIEW_ID = "86968070"; //<REPLACE_WITH_VIEW_ID>";

      // Create the DateRange object.
      $dateRange = new Google_Service_AnalyticsReporting_DateRange();
      $dateRange->setStartDate("30daysAgo");
      $dateRange->setEndDate("today");

      // Create Label dimension
      $label = new Google_Service_AnalyticsReporting_Dimension();
      $label->setName("ga:eventLabel");

      // Create the segment dimension.
      $segmentDimensions = new Google_Service_AnalyticsReporting_Dimension();
      $segmentDimensions->setName("ga:segment");

      // Create Dimension Filter.
      $dimensionFilter = new Google_Service_AnalyticsReporting_SegmentDimensionFilter();
      $dimensionFilter->setDimensionName("ga:eventCategory");
      $dimensionFilter->setOperator("EXACT");
      $dimensionFilter->setExpressions(array("keyboard"));

      // Create Segment Filter Clause.
      $segmentFilterClause = new Google_Service_AnalyticsReporting_SegmentFilterClause();
      $segmentFilterClause->setDimensionFilter($dimensionFilter);

      // Create the Or Filters for Segment.
      $orFiltersForSegment = new Google_Service_AnalyticsReporting_OrFiltersForSegment();
      $orFiltersForSegment->setSegmentFilterClauses(array($segmentFilterClause));

      // Create the Simple Segment.
      $simpleSegment = new Google_Service_AnalyticsReporting_SimpleSegment();
      $simpleSegment->setOrFiltersForSegment(array($orFiltersForSegment));

      // Create the Segment Filters.
      $segmentFilter = new Google_Service_AnalyticsReporting_SegmentFilter();
      $segmentFilter->setSimpleSegment($simpleSegment);

      // Create the Segment Definition.
      $segmentDefinition = new Google_Service_AnalyticsReporting_SegmentDefinition();
      $segmentDefinition->setSegmentFilters(array($segmentFilter));

      // Create the Dynamic Segment.
      $dynamicSegment = new Google_Service_AnalyticsReporting_DynamicSegment();
      $dynamicSegment->setSessionSegment($segmentDefinition);
      $dynamicSegment->setName("Events with keyboard category");

      // Create the Segments object.
      $segment = new Google_Service_AnalyticsReporting_Segment();
      $segment->setDynamicSegment($dynamicSegment);


      // Create the Metrics object.
      $sessions = new Google_Service_AnalyticsReporting_Metric();
      $sessions->setExpression("ga:totalEvents");
      $sessions->setAlias("totalEvents");


      // Create the ReportRequest object.
      $request = new Google_Service_AnalyticsReporting_ReportRequest();
      $request->setViewId($VIEW_ID);
      $request->setDateRanges(array($dateRange));
      $request->setDimensions(array($label, $segmentDimensions));
      $request->setSegments(array($segment));
      $request->setMetrics(array($sessions));

      $body = new Google_Service_AnalyticsReporting_GetReportsRequest();
      $body->setReportRequests( array( $request) );
      return $analytics->reports->batchGet( $body );
    }

    /**
     * Parses the Analytics Reporting API V4 response and prepares SQL statement
     *
     * @param An Analytics Reporting API V4 response.
     */
    function generateSQL($reports) {
      $sql = '';
      for ( $reportIndex = 0; $reportIndex < count( $reports ); $reportIndex++ ) {
        $report = $reports[ $reportIndex ];
        $header = $report->getColumnHeader();
        $dimensionHeaders = $header->getDimensions();
        $metricHeaders = $header->getMetricHeader()->getMetricHeaderEntries();
        $rows = $report->getData()->getRows();

        for ( $rowIndex = 0; $rowIndex < count($rows); $rowIndex++) {
          $row = $rows[ $rowIndex ];
          $dimensions = $row->getDimensions();
          $metrics = $row->getMetrics();

          $sql .= "INSERT t_keyboard_downloads (keyboard_id, count) SELECT ".sqlv0($dimensions[0]).", {$metrics[0]->getValues()[0]}\n";
        }
      }
      return $sql;
    }
  }