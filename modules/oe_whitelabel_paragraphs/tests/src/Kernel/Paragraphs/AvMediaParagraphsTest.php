<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_whitelabel_paragraphs\Kernel\Paragraphs;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Url;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Test 'AV Media' paragraph.
 */
class AvMediaParagraphsTest extends ParagraphsTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->container->get('module_handler')->loadInclude('oe_paragraphs_media_field_storage', 'install');
    oe_paragraphs_media_field_storage_install(FALSE);

    $this->installEntitySchema('media');
    $this->installConfig([
      'media',
      'media_avportal',
      'oe_media',
      'oe_media_avportal',
      'oe_media_iframe',
      'oe_paragraphs_av_media',
    ]);

    $this->container->get('module_handler')->loadInclude('media', 'install');
    media_install();
  }

  /**
   * Test 'AV Media' paragraph rendering with allowed media sources.
   */
  public function testAvMediaParagraph(): void {
    $media_storage = $this->container->get('entity_type.manager')->getStorage('media');
    $fixtures_path = $this->container->get('extension.list.module')->getPath('oe_whitelabel_paragraphs') . '/tests/fixtures/';

    // Create an "Image" media.
    $file_1 = $this->container->get('file.repository')->writeData(file_get_contents($fixtures_path . 'example_1.jpeg'), 'public://example_1.jpeg');
    $file_1->setPermanent();
    $file_1->save();

    $media_image = $media_storage->create([
      'bundle' => 'image',
      'name' => 'Image',
      'oe_media_image' => [
        'target_id' => $file_1->id(),
      ],
    ]);
    $media_image->save();

    // Create a "Video iframe" media.
    $media_iframe = $media_storage->create([
      'bundle' => 'video_iframe',
      'name' => 'Video Iframe',
      'oe_media_iframe' => [
        'value' => '<iframe src="https://example.com"></iframe>',
      ],
    ]);
    $media_iframe->save();

    // Create a "Remote Video" media.
    $media_remote = $media_storage->create([
      'bundle' => 'remote_video',
      'name' => 'Remote Video',
      'oe_media_oembed_video' => [
        'value' => 'https://www.youtube.com/watch?v=tj8ByiJb1vM',
      ],
    ]);
    $media_remote->save();
    $partial_iframe_url = Url::fromRoute('media.oembed_iframe', [], [
      'query' => [
        'url' => 'https://www.youtube.com/watch?v=tj8ByiJb1vM',
      ],
    ])->toString();

    // Create an "AV Portal Video" media.
    $media_av_video = $media_storage->create([
      'bundle' => 'av_portal_video',
      'name' => 'AV Portal Video',
      'oe_media_avportal_video' => 'I-163162',
    ]);
    $media_av_video->save();

    // Create an "AV Portal Photo" media.
    $media_av_photo = $media_storage->create([
      'bundle' => 'av_portal_photo',
      'name' => 'AV Portal Photo',
      'oe_media_avportal_photo' => 'P-038924/00-15',
    ]);
    $media_av_photo->save();
    $remote_thumbnail_url_encrypted = Crypt::hashBase64('store2/4/P038924-35966.jpg');

    $values = [
      [
        'media' => $media_image->id(),
        'expected_src' => '/styles/oe_bootstrap_theme_medium_no_crop/public/example_1.jpeg',
        'selector' => 'img',
      ],
      [
        'media' => $media_iframe->id(),
        'expected_src' => 'https://example.com',
        'selector' => 'iframe',
      ],
      [
        'media' => $media_remote->id(),
        'expected_src' => $partial_iframe_url,
        'selector' => '.ratio-16x9 iframe',
      ],
      [
        'media' => $media_av_video->id(),
        'expected_src' => '//ec.europa.eu/avservices/play.cfm?ref=I-163162',
        'selector' => '.ratio-16x9 iframe',
      ],
      [
        'media' => $media_av_photo->id(),
        'expected_src' => "/styles/oe_bootstrap_theme_medium_no_crop/public/media_avportal_thumbnails/$remote_thumbnail_url_encrypted.jpg",
        'selector' => 'img',
      ],
    ];

    foreach ($values as $value) {
      $crawler = $this->renderAvMediaParagraph($value['media']);
      $this->assertMediaAddedWithExpectedSource($crawler, $value['expected_src'], $value['selector']);
    }
  }

  /**
   * Renders an AV Media paragraph given a media ID.
   *
   * @param int $media_id
   *   The media id.
   *
   * @return \Symfony\Component\DomCrawler\Crawler
   *   The DomCrawler of the rendered paragraph.
   */
  protected function renderAvMediaParagraph(string $media_id): Crawler {
    $paragraph_storage = $this->container->get('entity_type.manager')->getStorage('paragraph');
    $paragraph = $paragraph_storage->create([
      'type' => 'oe_av_media',
      'field_oe_media' => [
        'target_id' => $media_id,
      ],
    ]);
    $paragraph->save();

    $html = $this->renderParagraph($paragraph);
    return new Crawler($html);
  }

  /**
   * Assets that the media added is of the expected source.
   *
   * @param \Symfony\Component\DomCrawler\Crawler $crawler
   *   The DomCrawler of the rendered paragraph.
   * @param string $expected_src
   *   A partial of the expected source of the rendered media.
   * @param string $media_selector
   *   A selector to find the rendered media by inside its container.
   */
  protected function assertMediaAddedWithExpectedSource(Crawler $crawler, string $expected_src, string $media_selector): void {
    $element = $crawler->filter(".bcl-featured-media $media_selector");
    self::assertStringContainsString($expected_src, $element->attr('src'));
  }

}
