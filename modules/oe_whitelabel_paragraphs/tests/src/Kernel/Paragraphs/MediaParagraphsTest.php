<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_whitelabel_paragraphs\Kernel\Paragraphs;

use Drupal\Core\Url;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Tests the rendering of paragraph types with media fields.
 */
class MediaParagraphsTest extends ParagraphsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'oe_media_oembed_mock',
  ];

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
      'oe_media',
      'oe_paragraphs_media',
      'media_avportal',
      'oe_media_avportal',
      'oe_paragraphs_banner',
      'oe_paragraphs_iframe_media',
      'options',
      'oe_media_iframe',
    ]);
    // Call the install hook of the Media module.
    $this->container->get('module_handler')->loadInclude('media', 'install');
    media_install();
  }

  /**
   * Test 'text with featured media' paragraph rendering.
   */
  public function testFeaturedMedia(): void {
    $image_file = \Drupal::service('file.repository')->writeData(file_get_contents(\Drupal::service('extension.list.module')->getPath('oe_whitelabel_paragraphs') . '/tests/fixtures/example_1.jpeg'), 'public://example_1.jpeg');
    $image_file->setPermanent();
    $image_file->save();

    $media_storage = $this->container->get('entity_type.manager')->getStorage('media');
    $media = $media_storage->create([
      'bundle' => 'image',
      'name' => 'test image',
      'oe_media_image' => [
        'target_id' => $image_file->id(),
        'alt' => 'Alt en',
      ],
    ]);
    $media->save();

    $paragraph_storage = $this->container->get('entity_type.manager')->getStorage('paragraph');
    $paragraph = $paragraph_storage->create([
      'type' => 'oe_text_feature_media',
      'field_oe_title' => 'Media Title',
      'field_oe_plain_text_long' => 'Media Caption',
      'field_oe_media' => [
        'target_id' => $media->id(),
      ],
    ]);
    $paragraph->save();

    // Testing: Image without wrapper.
    $html = $this->renderParagraph($paragraph);
    $crawler = new Crawler($html);

    $this->assertCount(1, $crawler->filter('div.row'));
    $this->assertCount(1, $crawler->filter('div.col-12.col-md-4'));
    $this->assertCount(0, $crawler->filter('h2'));
    $this->assertCount(0, $crawler->filter('div.col-12.col-md-6.order-md-1'));
    $this->assertCount(0, $crawler->filter('div.col-12.col-md-6.order-md-2'));
    $figure = $crawler->filter('figure');
    $this->assertCount(1, $figure);
    $this->assertCount(1, $figure->filter('img.img-fluid'));
    $this->assertCount(0, $figure->filter('iframe'));
    $this->assertStringContainsString(
      $image_file->getFilename(),
      $figure->html()
    );
    $this->assertEquals('Media Caption', trim($figure->filter('figcaption.bg-light.p-3')->text()));

    // Testing: Image with wrapper aligned to left.
    $paragraph->get('field_oe_text_long')->setValue('Media Full Text');
    $paragraph->get('oe_paragraphs_variant')->setValue('left_featured');
    $paragraph->save();
    $html = $this->renderParagraph($paragraph);
    $crawler = new Crawler($html);

    $this->assertCount(1, $crawler->filter('div.row'));
    $this->assertCount(0, $crawler->filter('div.col-12.col-md-4'));
    $this->assertCount(1, $crawler->filter('h2'));
    $this->assertCount(1, $crawler->filter('div.col-12.col-md-6.order-md-1'));
    $this->assertCount(1, $crawler->filter('div.col-12.col-md-6.order-md-2'));
    $figure = $crawler->filter('figure');
    $this->assertCount(1, $figure);
    $this->assertCount(1, $figure->filter('img.img-fluid'));
    $this->assertCount(0, $figure->filter('iframe'));
    $this->assertStringContainsString(
      $image_file->getFilename(),
      $figure->html()
    );
    $full_text = $crawler->filter('div.col-12.col-md-6.order-md-1');
    $this->assertEquals('Media Full Text', trim($full_text->text()));
    $this->assertEquals('Media Caption', trim($figure->filter('figcaption.bg-light.p-3')->text()));

    // Testing: Image with wrapper aligned to right.
    $paragraph->get('field_oe_text_long')->setValue('Media Full Text');
    $paragraph->get('oe_paragraphs_variant')->setValue('right_featured');
    $paragraph->save();
    $html = $this->renderParagraph($paragraph);
    $crawler = new Crawler($html);

    $this->assertCount(1, $crawler->filter('div.row'));
    $this->assertCount(0, $crawler->filter('div.col-12.col-md-4'));
    $this->assertCount(1, $crawler->filter('h2'));
    $this->assertCount(1, $crawler->filter('div.col-12.col-md-6.order-md-1'));
    $this->assertCount(1, $crawler->filter('div.col-12.col-md-6.order-md-2'));
    $figure = $crawler->filter('figure');
    $this->assertCount(1, $figure);
    $this->assertCount(1, $figure->filter('img.img-fluid'));
    $this->assertCount(0, $figure->filter('iframe'));
    $this->assertStringContainsString(
      $image_file->getFilename(),
      $figure->html()
    );
    $full_text = $crawler->filter('div.col-12.col-md-6.order-md-2');
    $this->assertEquals('Media Full Text', trim($full_text->text()));
    $this->assertEquals('Media Caption', trim($figure->filter('figcaption.bg-light.p-3')->text()));

    // Create a remote video and add it to the paragraph.
    $media = $media_storage->create([
      'bundle' => 'remote_video',
      'oe_media_oembed_video' => [
        'value' => 'https://www.youtube.com/watch?v=1-g73ty9v04',
      ],
    ]);
    $media->save();
    $paragraph->set('field_oe_media', ['target_id' => $media->id()]);
    $paragraph->save();

    $paragraph_storage = $this->container->get('entity_type.manager')->getStorage('paragraph');
    $paragraph = $paragraph_storage->create([
      'type' => 'oe_text_feature_media',
      'field_oe_title' => 'Media Title',
      'field_oe_plain_text_long' => 'Media Caption',
      'field_oe_media' => [
        'target_id' => $media->id(),
      ],
    ]);
    $paragraph->save();

    // Testing: Iframe without wrapper.
    $html = $this->renderParagraph($paragraph);
    $crawler = new Crawler($html);

    $this->assertCount(1, $crawler->filter('div.row'));
    $this->assertCount(1, $crawler->filter('div.col-12.col-md-4'));
    $this->assertCount(0, $crawler->filter('h2'));
    $this->assertCount(0, $crawler->filter('div.col-12.col-md-6.order-md-1'));
    $this->assertCount(0, $crawler->filter('div.col-12.col-md-6.order-md-2'));
    $this->assertCount(1, $crawler->filter('div.ratio.ratio-16x9 > iframe'));
    $figure = $crawler->filter('figure');
    $this->assertCount(1, $figure);
    $this->assertCount(0, $figure->filter('img.img-fluid'));
    $this->assertCount(1, $figure->filter('iframe'));
    // Assert remote video is rendered properly.
    $video_iframe = $crawler->filter('iframe');
    $partial_iframe_url = Url::fromRoute('media.oembed_iframe', [], [
      'query' => [
        'url' => 'https://www.youtube.com/watch?v=1-g73ty9v04',
      ],
    ])->toString();
    $this->assertStringContainsString($partial_iframe_url, $video_iframe->attr('src'));
    $this->assertEquals('Media Caption', trim($figure->filter('figcaption.bg-light.p-3')->text()));

    // Testing: Iframe with wrapper aligned to left.
    $paragraph->get('field_oe_text_long')->setValue('Media Full Text');
    $paragraph->get('oe_paragraphs_variant')->setValue('left_featured');
    $paragraph->save();
    $html = $this->renderParagraph($paragraph);
    $crawler = new Crawler($html);

    $this->assertCount(1, $crawler->filter('div.row'));
    $this->assertCount(0, $crawler->filter('div.col-12.col-md-4'));
    $this->assertCount(1, $crawler->filter('h2'));
    $this->assertCount(1, $crawler->filter('div.col-12.col-md-6.order-md-1'));
    $this->assertCount(1, $crawler->filter('div.col-12.col-md-6.order-md-2'));
    $this->assertCount(1, $crawler->filter('div.ratio.ratio-16x9 > iframe'));
    $figure = $crawler->filter('figure');
    $this->assertCount(1, $figure);
    $this->assertCount(0, $figure->filter('img.img-fluid'));
    $this->assertCount(1, $figure->filter('iframe'));
    // Assert remote video is rendered properly.
    $video_iframe = $crawler->filter('iframe');
    $partial_iframe_url = Url::fromRoute('media.oembed_iframe', [], [
      'query' => [
        'url' => 'https://www.youtube.com/watch?v=1-g73ty9v04',
      ],
    ])->toString();
    $this->assertStringContainsString($partial_iframe_url, $video_iframe->attr('src'));
    $full_text = $crawler->filter('div.col-12.col-md-6.order-md-1');
    $this->assertEquals('Media Full Text', trim($full_text->text()));
    $this->assertEquals('Media Caption', trim($figure->filter('figcaption.bg-light.p-3')->text()));

    // Testing: Iframe with wrapper aligned to right.
    $paragraph->get('field_oe_text_long')->setValue('Media Full Text');
    $paragraph->get('oe_paragraphs_variant')->setValue('right_featured');
    $paragraph->save();
    $html = $this->renderParagraph($paragraph);
    $crawler = new Crawler($html);

    $this->assertCount(1, $crawler->filter('div.row'));
    $this->assertCount(0, $crawler->filter('div.col-12.col-md-4'));
    $this->assertCount(1, $crawler->filter('h2'));
    $this->assertCount(1, $crawler->filter('div.col-12.col-md-6.order-md-1'));
    $this->assertCount(1, $crawler->filter('div.col-12.col-md-6.order-md-2'));
    $this->assertCount(1, $crawler->filter('div.ratio.ratio-16x9 > iframe'));
    $figure = $crawler->filter('figure');
    $this->assertCount(1, $figure);
    $this->assertCount(0, $figure->filter('img.img-fluid'));
    $this->assertCount(1, $figure->filter('iframe'));
    // Assert remote video is rendered properly.
    $video_iframe = $crawler->filter('iframe');
    $partial_iframe_url = Url::fromRoute('media.oembed_iframe', [], [
      'query' => [
        'url' => 'https://www.youtube.com/watch?v=1-g73ty9v04',
      ],
    ])->toString();
    $this->assertStringContainsString($partial_iframe_url, $video_iframe->attr('src'));
    $full_text = $crawler->filter('div.col-12.col-md-6.order-md-2');
    $this->assertEquals('Media Full Text', trim($full_text->text()));
    $this->assertEquals('Media Caption', trim($figure->filter('figcaption.bg-light.p-3')->text()));

    // Create an avportal video and add it to the paragraph.
    $media = $media_storage->create([
      'bundle' => 'av_portal_video',
      'oe_media_avportal_video' => 'I-163162',
    ]);
    $media->save();
    $paragraph->set('field_oe_media', ['target_id' => $media->id()]);
    $paragraph->save();

    $html = $this->renderParagraph($paragraph);
    $crawler = new Crawler($html);

    $this->assertCount(1, $crawler->filter('div.row'));
    $this->assertCount(0, $crawler->filter('div.col-12.col-md-4'));
    $this->assertCount(1, $crawler->filter('h2'));
    $this->assertCount(1, $crawler->filter('div.col-12.col-md-6.order-md-1'));
    $this->assertCount(1, $crawler->filter('div.col-12.col-md-6.order-md-2'));
    $this->assertCount(1, $crawler->filter('div.ratio.ratio-16x9 > iframe'));
    $figure = $crawler->filter('figure');
    $this->assertCount(1, $figure);
    $this->assertCount(0, $figure->filter('img.img-fluid'));
    $this->assertCount(1, $figure->filter('iframe'));
    // Assert remote video is rendered properly.
    $video_iframe = $crawler->filter('iframe');
    $this->assertStringContainsString('ec.europa.eu/avservices/play.cfm?ref=I-163162', $video_iframe->attr('src'));
    $full_text = $crawler->filter('div.col-12.col-md-6.order-md-2');
    $this->assertEquals('Media Full Text', trim($full_text->text()));
    $this->assertEquals('Media Caption', trim($figure->filter('figcaption.bg-light.p-3')->text()));

    // Testing: Link and media title.
    $paragraph->get('field_oe_text_long')->setValue('Media Full Text');
    $paragraph->get('oe_paragraphs_variant')->setValue('right_featured');
    $paragraph->get('field_oe_feature_media_title')->setValue('Text title');
    $paragraph->get('field_oe_link')->setValue([
      'uri' => 'https://example1',
      'title' => 'Example 1',
    ]);
    $paragraph->save();
    $html = $this->renderParagraph($paragraph);
    $crawler = new Crawler($html);

    $this->assertCount(1, $crawler->filter('div.row'));
    $this->assertCount(0, $crawler->filter('div.col-12.col-md-4'));
    $media_title_text = $crawler->filter('h5.text-secondary');
    $this->assertEquals('Text title', trim($media_title_text->text()));
    $link = $crawler->filter('a[href="https://example1"]');
    $this->assertCount(1, $link);
    $this->assertEquals('Example 1', trim($link->text()));
  }

  /**
   * Test 'banner' paragraph rendering.
   */
  public function testBanner(): void {
    // Create English file.
    $en_file = \Drupal::service('file.repository')->writeData(file_get_contents(\Drupal::service('extension.list.module')->getPath('oe_whitelabel_paragraphs') . '/tests/fixtures/example_1.jpeg'), 'public://example_1_en.jpeg');
    $en_file->setPermanent();
    $en_file->save();

    // Create a media.
    $media_storage = $this->container->get('entity_type.manager')->getStorage('media');
    $media = $media_storage->create([
      'bundle' => 'image',
      'name' => 'test image en',
      'oe_media_image' => [
        'target_id' => $en_file->id(),
        'alt' => 'Alt en',
      ],
    ]);
    $media->save();

    $paragraph_storage = $this->container->get('entity_type.manager')->getStorage('paragraph');
    $paragraph = $paragraph_storage->create([
      'type' => 'oe_banner',
      'oe_paragraphs_variant' => 'oe_banner_image',
      'field_oe_title' => 'Banner',
      'field_oe_text' => 'Description',
      'field_oe_link' => [
        'uri' => 'http://www.example.com/',
        'title' => 'Example',
      ],
      'field_oe_media' => [
        'target_id' => $media->id(),
      ],
      'field_oe_banner_size' => 'large',
      'field_oe_banner_alignment' => 'centered',
    ]);
    $paragraph->save();

    // Variant - image / Modifier - hero_center / Full width - No.
    $html = $this->renderParagraph($paragraph);
    $crawler = new Crawler($html);

    $this->assertCount(1, $crawler->filter('.bcl-banner.bg-lighter.text-dark.overlay.text-center.hero'));
    $image_element = $crawler->filter('.bcl-banner__image');
    $this->assertCount(1, $image_element);
    $this->assertStringContainsString(
      'url(' . \Drupal::service('file_url_generator')->generateAbsoluteString($en_file->getFileUri()) . ')',
      $image_element->attr('style')
    );
    $this->assertBannerRendering($crawler);
    $this->assertCount(0, $crawler->filter('.bcl-banner.full-width'));

    // Variant - image / Modifier - hero_left / Full width - No / Title - NULL.
    $paragraph->get('field_oe_banner_alignment')->setValue('left');
    $paragraph->get('field_oe_title')->setValue(NULL);
    $paragraph->save();

    $html = $this->renderParagraph($paragraph);
    $crawler = new Crawler($html);

    $this->assertCount(0, $crawler->filter('.bcl-banner.bg-lighter.text-dark.overlay.text-center.hero'));
    $this->assertCount(1, $crawler->filter('.bcl-banner.bg-lighter.text-dark.overlay.hero'));
    $image_element = $crawler->filter('.bcl-banner__image');
    $this->assertCount(1, $image_element);
    $this->assertStringContainsString(
      'url(' . \Drupal::service('file_url_generator')->generateAbsoluteString($en_file->getFileUri()) . ')',
      $image_element->attr('style')
    );
    $this->assertBannerRendering($crawler, ['title' => NULL]);
    $this->assertCount(0, $crawler->filter('.bcl-banner.full-width'));

    // Variant - image / Modifier - page_center / Full width - No / Link - NULL.
    $paragraph->get('field_oe_banner_size')->setValue('medium');
    $paragraph->get('field_oe_banner_alignment')->setValue('centered');
    $paragraph->get('field_oe_title')->setValue('Banner');
    $paragraph->get('field_oe_link')->setValue(NULL);
    $paragraph->save();
    $html = $this->renderParagraph($paragraph);
    $crawler = new Crawler($html);

    $this->assertCount(1, $crawler->filter('.bcl-banner.bg-lighter.text-dark.overlay.text-center'));
    $this->assertCount(0, $crawler->filter('.bcl-banner.hero'));
    $image_element = $crawler->filter('.bcl-banner__image');
    $this->assertCount(1, $image_element);
    $this->assertStringContainsString(
      'url(' . \Drupal::service('file_url_generator')->generateAbsoluteString($en_file->getFileUri()) . ')',
      $image_element->attr('style')
    );
    $this->assertBannerRendering($crawler, ['link_label' => NULL]);
    $this->assertCount(0, $crawler->filter('.bcl-banner.full-width'));

    // Variant - image / Modifier - page_left / Full width - Yes.
    $paragraph->get('field_oe_banner_size')->setValue('medium');
    $paragraph->get('field_oe_banner_alignment')->setValue('left');
    $paragraph->get('field_oe_banner_full_width')->setValue('1');
    $paragraph->get('field_oe_link')->setValue([
      'uri' => 'http://www.example.com/',
      'title' => 'Example',
    ]);
    $paragraph->save();
    $html = $this->renderParagraph($paragraph);
    $crawler = new Crawler($html);

    $this->assertCount(1, $crawler->filter('.bcl-banner.bg-lighter.text-dark.overlay.full-width'));
    $this->assertCount(0, $crawler->filter('.bcl-banner.text-center'));
    $this->assertCount(0, $crawler->filter('.bcl-banner.hero'));
    $image_element = $crawler->filter('.bcl-banner__image');
    $this->assertCount(1, $image_element);
    $this->assertStringContainsString(
      'url(' . \Drupal::service('file_url_generator')->generateAbsoluteString($en_file->getFileUri()) . ')',
      $image_element->attr('style')
    );
    $this->assertBannerRendering($crawler);
    $this->assertCount(1, $crawler->filter('.bcl-banner.full-width'));

    // Variant - image-shade / Modifier - hero_center / Full width - Yes.
    $paragraph->get('oe_paragraphs_variant')->setValue('oe_banner_image_shade');
    $paragraph->get('field_oe_banner_size')->setValue('large');
    $paragraph->get('field_oe_banner_alignment')->setValue('centered');
    $paragraph->save();
    $html = $this->renderParagraph($paragraph);
    $crawler = new Crawler($html);

    $this->assertCount(1, $crawler->filter('.bcl-banner.bg-lighter.shade.text-center.hero.full-width'));
    $image_element = $crawler->filter('.bcl-banner__image');
    $this->assertCount(1, $image_element);
    $this->assertStringContainsString(
      'url(' . \Drupal::service('file_url_generator')->generateAbsoluteString($en_file->getFileUri()) . ')',
      $image_element->attr('style')
    );
    $this->assertBannerRendering($crawler);
    $this->assertCount(1, $crawler->filter('.bcl-banner.full-width'));

    // Variant - image-shade / Modifier - hero_left / Full width - Yes.
    $paragraph->get('field_oe_banner_size')->setValue('large');
    $paragraph->get('field_oe_banner_alignment')->setValue('left');
    $paragraph->save();
    $html = $this->renderParagraph($paragraph);
    $crawler = new Crawler($html);

    $this->assertCount(1, $crawler->filter('.bcl-banner.bg-lighter.shade.hero.full-width'));
    $this->assertCount(0, $crawler->filter('.bcl-banner.text-center'));
    $image_element = $crawler->filter('.bcl-banner__image');
    $this->assertCount(1, $image_element);
    $this->assertStringContainsString(
      'url(' . \Drupal::service('file_url_generator')->generateAbsoluteString($en_file->getFileUri()) . ')',
      $image_element->attr('style')
    );
    $this->assertBannerRendering($crawler);
    $this->assertCount(1, $crawler->filter('.bcl-banner.full-width'));

    // Variant - image-shade / Modifier - page_center / Full width - No.
    $paragraph->get('field_oe_banner_size')->setValue('medium');
    $paragraph->get('field_oe_banner_alignment')->setValue('centered');
    $paragraph->get('field_oe_banner_full_width')->setValue('0');
    $paragraph->save();
    $html = $this->renderParagraph($paragraph);
    $crawler = new Crawler($html);

    $this->assertCount(1, $crawler->filter('.bcl-banner.bg-lighter.shade.text-center'));
    $this->assertCount(0, $crawler->filter('.bcl-banner.hero'));
    $image_element = $crawler->filter('.bcl-banner__image');
    $this->assertCount(1, $image_element);
    $this->assertStringContainsString(
      'url(' . \Drupal::service('file_url_generator')->generateAbsoluteString($en_file->getFileUri()) . ')',
      $image_element->attr('style')
    );
    $this->assertBannerRendering($crawler);
    $this->assertCount(0, $crawler->filter('.bcl-banner.full-width'));

    // Variant - image-shade / Modifier - page_left / Full width - No.
    $paragraph->get('field_oe_banner_size')->setValue('medium');
    $paragraph->get('field_oe_banner_alignment')->setValue('left');
    $paragraph->save();
    $html = $this->renderParagraph($paragraph);
    $crawler = new Crawler($html);

    $this->assertCount(1, $crawler->filter('.bcl-banner.bg-lighter.shade'));
    $this->assertCount(0, $crawler->filter('.bcl-banner.hero'));
    $this->assertCount(0, $crawler->filter('.bcl-banner.text-center'));
    $image_element = $crawler->filter('.bcl-banner__image');
    $this->assertCount(1, $image_element);
    $this->assertStringContainsString(
      'url(' . \Drupal::service('file_url_generator')->generateAbsoluteString($en_file->getFileUri()) . ')',
      $image_element->attr('style')
    );
    $this->assertBannerRendering($crawler);
    $this->assertCount(0, $crawler->filter('.bcl-banner.full-width'));

    // Variant - default / Modifier - hero_center / Full width - No.
    $paragraph->get('oe_paragraphs_variant')->setValue('default');
    $paragraph->get('field_oe_banner_size')->setValue('large');
    $paragraph->get('field_oe_banner_alignment')->setValue('centered');
    $paragraph->save();
    $html = $this->renderParagraph($paragraph);
    $crawler = new Crawler($html);

    $this->assertCount(1, $crawler->filter('.bcl-banner.bg-lighter.text-dark.text-center.hero'));

    // No image should be displayed on 'default' variant.
    $image_element = $crawler->filter('.bcl-banner__image');
    $this->assertCount(0, $image_element);

    $this->assertBannerRendering($crawler);
    $this->assertCount(0, $crawler->filter('.bcl-banner.full-width'));

    // Variant - default / Modifier - hero_left / Full width - Yes.
    $paragraph->get('field_oe_banner_size')->setValue('large');
    $paragraph->get('field_oe_banner_alignment')->setValue('left');
    $paragraph->get('field_oe_banner_full_width')->setValue('1');
    $paragraph->save();
    $html = $this->renderParagraph($paragraph);
    $crawler = new Crawler($html);

    $this->assertCount(1, $crawler->filter('.bcl-banner.bg-lighter.text-dark.hero.full-width'));
    $this->assertCount(0, $crawler->filter('.bcl-banner.text-center'));

    // No image should be displayed on 'default' variant.
    $image_element = $crawler->filter('.bcl-banner__image');
    $this->assertCount(0, $image_element);

    $this->assertBannerRendering($crawler);
    $this->assertCount(1, $crawler->filter('.bcl-banner.full-width'));

    // Variant - default / Modifier - page_center / Full width - Yes.
    $paragraph->get('field_oe_banner_size')->setValue('medium');
    $paragraph->get('field_oe_banner_alignment')->setValue('centered');
    $paragraph->save();
    $html = $this->renderParagraph($paragraph);
    $crawler = new Crawler($html);

    $this->assertCount(1, $crawler->filter('.bcl-banner.bg-lighter.text-dark.text-center.full-width'));
    $this->assertCount(0, $crawler->filter('.bcl-banner.hero'));

    // No image should be displayed on 'default' variant.
    $image_element = $crawler->filter('.bcl-banner__image');
    $this->assertCount(0, $image_element);

    $this->assertBannerRendering($crawler);
    $this->assertCount(1, $crawler->filter('.bcl-banner.full-width'));

    // Variant - default / Modifier - page_left / Full width - Yes.
    $paragraph->get('field_oe_banner_size')->setValue('medium');
    $paragraph->get('field_oe_banner_alignment')->setValue('left');
    $paragraph->save();
    $html = $this->renderParagraph($paragraph);
    $crawler = new Crawler($html);

    $this->assertCount(1, $crawler->filter('.bcl-banner.bg-lighter.text-dark.full-width'));
    $this->assertCount(0, $crawler->filter('.bcl-banner.hero'));
    $this->assertCount(0, $crawler->filter('.bcl-banner.text-center'));

    // No image should be displayed on 'default' variant.
    $image_element = $crawler->filter('.bcl-banner__image');
    $this->assertCount(0, $image_element);

    $this->assertBannerRendering($crawler);
    $this->assertCount(1, $crawler->filter('.bcl-banner.full-width'));

    // Variant - primary / Modifier - hero_center / Full width - Yes.
    $paragraph->get('oe_paragraphs_variant')->setValue('oe_banner_primary');
    $paragraph->get('field_oe_banner_size')->setValue('large');
    $paragraph->get('field_oe_banner_alignment')->setValue('centered');
    $paragraph->save();
    $html = $this->renderParagraph($paragraph);
    $crawler = new Crawler($html);

    $this->assertCount(1, $crawler->filter('.bcl-banner.bg-primary.text-white.text-center.hero.full-width'));

    // No image should be displayed on 'default' variant.
    $image_element = $crawler->filter('.bcl-banner__image');
    $this->assertCount(0, $image_element);

    $this->assertBannerRendering($crawler);
    $this->assertCount(1, $crawler->filter('.bcl-banner.full-width'));

    // Variant - primary / Modifier - hero_left / Full width - Yes.
    $paragraph->get('field_oe_banner_size')->setValue('large');
    $paragraph->get('field_oe_banner_alignment')->setValue('left');
    $paragraph->save();
    $html = $this->renderParagraph($paragraph);
    $crawler = new Crawler($html);

    $this->assertCount(1, $crawler->filter('.bcl-banner.bg-primary.text-white.hero.full-width'));
    $this->assertCount(0, $crawler->filter('.bcl-banner.text-center'));

    // No image should be displayed on 'default' variant.
    $image_element = $crawler->filter('.bcl-banner__image');
    $this->assertCount(0, $image_element);

    $this->assertBannerRendering($crawler);
    $this->assertCount(1, $crawler->filter('.bcl-banner.full-width'));

    // Variant - primary / Modifier - page_center / Full width - Yes.
    $paragraph->get('field_oe_banner_size')->setValue('medium');
    $paragraph->get('field_oe_banner_alignment')->setValue('centered');
    $paragraph->save();
    $html = $this->renderParagraph($paragraph);
    $crawler = new Crawler($html);

    $this->assertCount(1, $crawler->filter('.bcl-banner.bg-primary.text-white.text-center.full-width'));
    $this->assertCount(0, $crawler->filter('.bcl-banner.hero'));

    // No image should be displayed on 'default' variant.
    $image_element = $crawler->filter('.bcl-banner__image');
    $this->assertCount(0, $image_element);

    $this->assertBannerRendering($crawler);
    $this->assertCount(1, $crawler->filter('.bcl-banner.full-width'));

    // Variant - primary / Modifier - page_left / Full width - No.
    $paragraph->get('field_oe_banner_size')->setValue('medium');
    $paragraph->get('field_oe_banner_alignment')->setValue('left');
    $paragraph->get('field_oe_banner_full_width')->setValue('0');
    $paragraph->save();
    $html = $this->renderParagraph($paragraph);
    $crawler = new Crawler($html);

    $this->assertCount(1, $crawler->filter('.bcl-banner.bg-primary.text-white'));
    $this->assertCount(0, $crawler->filter('.bcl-banner.hero'));
    $this->assertCount(0, $crawler->filter('.bcl-banner.text-center'));

    // No image should be displayed on 'default' variant.
    $image_element = $crawler->filter('.bcl-banner__image');
    $this->assertCount(0, $image_element);

    $this->assertBannerRendering($crawler);
    $this->assertCount(0, $crawler->filter('.bcl-banner.full-width'));

    // Test the rendering when the legacy "banner type" field is filled in.
    $paragraph->set('field_oe_banner_type', 'hero_center')->save();
    // Since the new size and alignment fields are filled in, they are used
    // for the rendering.
    $html = $this->renderParagraph($paragraph);
    $crawler = new Crawler($html);
    $this->assertCount(1, $crawler->filter('.bcl-banner.bg-primary.text-white'));
    $this->assertCount(0, $crawler->filter('.bcl-banner.hero'));
    $this->assertCount(0, $crawler->filter('.bcl-banner.text-center'));

    // When the new fields are empty, the old banner type field is used.
    $paragraph->set('field_oe_banner_size', NULL);
    $paragraph->set('field_oe_banner_alignment', NULL);
    $paragraph->save();
    $html = $this->renderParagraph($paragraph);
    $crawler = new Crawler($html);
    $this->assertCount(1, $crawler->filter('.bcl-banner.bg-primary.text-white.hero.text-center'));

    // Create a media using AV Portal image and add it to the paragraph.
    $media = $media_storage->create([
      'bundle' => 'av_portal_photo',
      'oe_media_avportal_photo' => 'P-038924/00-15',
      'uid' => 0,
      'status' => 1,
    ]);
    $media->save();

    $paragraph = $paragraph_storage->create([
      'type' => 'oe_banner',
      'oe_paragraphs_variant' => 'oe_banner_image',
      'field_oe_text' => 'Description',
      'field_oe_media' => [
        'target_id' => $media->id(),
      ],
      'field_oe_banner_size' => 'large',
      'field_oe_banner_alignment' => 'centered',
    ]);
    $paragraph->save();
    $html = $this->renderParagraph($paragraph);
    $crawler = new Crawler($html);

    $image_element = $crawler->filter('.bcl-banner__image');
    $this->assertCount(1, $image_element);
    $this->assertStringContainsString(
      'url(' . (\Drupal::service('file_url_generator')->generateAbsoluteString('avportal://P-038924/00-15.jpg')) . ')',
      $image_element->attr('style')
    );
  }

  /**
   * Assert Banner is rendering correctly.
   *
   * @param \Symfony\Component\DomCrawler\Crawler $crawler
   *   The DomCrawler where to check the element.
   * @param array $expected_values
   *   The text values for paragraph fields.
   */
  protected function assertBannerRendering(Crawler $crawler, array $expected_values = []): void {
    $values = array_merge([
      'title' => 'Banner',
      'description' => 'Description',
      'link_label' => 'Example',
    ], $expected_values);
    // Check optional title.
    if ($values['title'] !== NULL) {
      $this->assertEquals($values['title'], trim($crawler->filter('.bcl-banner__content div')->text()));
    }
    else {
      $this->assertCount(0, $crawler->filter('.bcl-banner__content div'));
    }
    // Check description text.
    $this->assertEquals($values['description'], trim($crawler->filter('.bcl-banner__content p')->text()));
    // Check optional button.
    if ($values['link_label'] !== NULL) {
      $this->assertCount(1, $crawler->filter('svg.bi.icon--fluid'));
      $this->assertStringContainsString($values['link_label'], trim($crawler->filter('a.btn')->text()));
      $this->assertStringContainsString('#chevron-right', trim($crawler->filter('a.btn')->html()));
    }
    else {
      $this->assertCount(0, $crawler->filter('a.btn'));
    }
  }

}
