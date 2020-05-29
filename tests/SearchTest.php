<?php declare(strict_types=1);

  namespace com\keyman\api\tests;

  require_once(__DIR__ . '/../tools/base.inc.php');
  require_once(__DIR__ . '/../script/search/search.inc.php');
  require_once(__DIR__ . '/TestUtils.inc.php');

  use PHPUnit\Framework\TestCase;
  use Swaggest\JsonSchema\Schema;
  use Swaggest\JsonSchema\Context;

  final class SearchTest extends TestCase
  {
    private const SchemaFilename = "/search/1.0.2/search.json";

    public function testSimpleSearchResultValidatesAgainstSchema(): void
    {
      $options = new Context();
      $options->remoteRefProvider = new ResolveLocalSchemas();
      $schema = Schema::import(SearchTest::SchemaFilename, $options);

      $s = new \KeyboardSearch();
      if(!empty($platform)) {
        $s->SetPlatform($platform);
      }
      $s->GetSearchMatches('thai');
      $json = $s->WriteSearchResults();

      // Whoa, PHP does *not* round-trip JSON cleanly. This however takes our output and transforms it
      // to something that passes our schema validation
      // TODO(lowpri): find a way to skip this by emitting clean JSON object from WriteSearchResults()
      $json = json_decode(json_encode($json));

      // This will throw an exception if it does not pass
      $schema->in($json);

      // Once we get here we know this test has passed so make PHPUnit happy
      $this->assertTrue(true);
    }
  }


