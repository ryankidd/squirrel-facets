<?php

namespace Squirrel\Facets\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Squirrel\Facets\Facets;
use Squirrel\Facets\Native\Indexer;
use WP_Query;
use WP_Post;

class IndexTest extends TestCase {

	/**
	 * Tests the crud of the table
	 * - runs twice in case the table already exists when invoked
	 */
	public function test_drop_create_exists_drop_create() {
		$index = Facets::instance()->provider->index;

		if ( $index->exists() ) {
			$dropped = $index->drop();

			$this->assertTrue( $dropped );
		}

		$this->assertFalse( $index->exists() );

		$created = $index->create();

		$this->assertTrue( $created );
		$this->assertTrue( $index->exists() );

		$dropped = $index->drop();

		$this->assertTrue( $dropped );

		$this->assertFalse( $index->exists() );

		$created = $index->create();

		$this->assertTrue( $created );
		$this->assertTrue( $index->exists() );
	}

	/**
	 * Tests the indexing process
	 *
	 * @depends test_drop_create_exists_drop_create
	 */
	public function test_indexer() {
		$provider = Facets::instance()->provider;
		$index    = $provider->index;
		$indexer  = new Indexer( $index, $provider );

		$results = $indexer->start();

		while ( ! $results['complete'] ) {
			$results = $indexer->next();
		}

		$stats = $index->stats();

		// 240 is how many facets are created by default with the tests/wptest.xml file
		return $this->assertGreaterThan( 0, $stats['total'] );
	}

	/**
	 * Tests that the query is returning facets
	 *
	 * @depends test_indexer
	 */
	public function test_query() {
		$query = new WP_Query(
			[
				'ignore_sticky_posts' => true,
				'post_type'           => 'any',
				'facets'              => [
					'category',
					'post_tag',
				],
			]
		);

		$results = [
			$this->assertArrayHasKey( 'category', $query->facets ),
			$this->assertArrayHasKey( 'post_tag', $query->facets ),
		];

		return $this->assertTrue( ! in_array( false, $results, true ) );
	}

	/**
	 * List of tags to add to our sample post
	 *
	 * @var array
	 */
	protected array $post_tags = [
		'Fail',
		'FTW',
		'Fun',
	];

	/**
	 * Create a post and ensure there are facets for the post
	 *
	 * @depends test_query
	 */
	public function test_create_post() {
		$index = Facets::instance()->provider->index;

		$post_id = wp_insert_post(
			[
				'post_type'    => 'post',
				'post_title'   => 'PHP Unit Test',
				'post_content' => '60% of the time, it works, every time',
				'post_status'  => 'publish',
			]
		);

		$this->assertIsInt( $post_id );

		$result = wp_set_post_terms( $post_id, $this->post_tags, 'post_tag', false );

		$this->assertIsArray( $result );
		$this->assertTrue( ! empty( $index->get_object( $post_id ) ) );

		return $post_id;
	}

	/**
	 * Read the post the determine that the facets are indeed the correct items
	 *
	 * @depends test_create_post
	 */
	public function test_read_post( $post_id ) {
		$query = new WP_Query(
			[
				'post_type'           => 'post',
				'post__in'            => [ $post_id ],
				'facets'              => [ 'post_tag' ],
				'ignore_sticky_posts' => true,
			]
		);

		$this->assertTrue( ! empty( $query->facets ) );
		$this->assertArrayHasKey( 'post_tag', $query->facets );
		$this->assertTrue( empty( array_diff( $this->post_tags, array_column( $query->facets['post_tag']->filters, 'label' ) ) ) );

		return $post_id;
	}

	/**
	 * Update a post to ensure that the indexing is being modified in real time
	 *
	 * @depends test_read_post
	 */
	public function test_update( $post_id ) {
		$terms = wp_get_post_terms( $post_id, 'post_tag' );

		$this->assertTrue( is_array( $terms ) );
		$this->assertTrue( ! empty( $terms ) );

		$count = count( $terms );

		array_pop( $terms );

		$result = wp_set_post_terms( $post_id, array_column( $terms, 'term_id' ), 'post_tag', false );

		$this->assertSame( $count - 1, count( $result ) );

		$query = new WP_Query(
			[
				'post_type'           => 'post',
				'post__in'            => [ $post_id ],
				'facets'              => [ 'post_tag' ],
				'ignore_sticky_posts' => true,
			]
		);

		$this->assertTrue( ! empty( $query->facets ) );
		$this->assertArrayHasKey( 'post_tag', $query->facets );
		$this->assertTrue( empty( array_diff( array_column( $terms, 'name' ), array_column( $query->facets['post_tag']->filters, 'label' ) ) ) );

		return $post_id;
	}

	/**
	 * Delete the post and test that the index entries are being removed in real time
	 *
	 * @depends test_read_post
	 */
	public function test_delete( $post_id ) {
		$index = Facets::instance()->provider->index;

		$this->assertTrue( ! empty( $index->get_object( $post_id ) ) );

		$post = wp_delete_post( $post_id, true );

		$this->assertTrue( $post instanceof WP_Post );
		$this->assertTrue( empty( $index->get_object( $post_id ) ) );

		return $post_id;
	}
}
