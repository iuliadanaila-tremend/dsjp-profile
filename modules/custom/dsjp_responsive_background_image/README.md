This is a very simple module that provides a method to call from within a preprocess function that will generate CSS media queries for a responsive background image for a specific HTML tag. This will allow you to serve a specific background image file depending on the browser window width, or the device pixel ratio. The provided method will return a style tag array ready to be inserted into the HTML `<head>` using `$vars['#attached']['html_head'][]` from within the preprocess function. It uses Drupal 8's Responsive Image Styles to generate the media queries and image paths. It supports the classic Image field and the newer Media field of type Image.

This module was created specifically for use in Paragraphs with image fields. For example, a Paragraph Type of "Hero", in which there is an image field, and where the image should be displayed as a background image on some part of the rendered Paragraph. It's possible that it would actually work for any fieldable entity (such as a Custom Block or a Node) though this hasn't been tested.

There is currently no UI for this module. It's meant for developers who are able to implement a `hook_preprocess_HOOK()`. It is really not difficult to implement, however. I'll try to walk you through the process. If you are already using Responsive Images or Paragraphs, you can probably skip most of these steps.

##Basic overview of steps to use this module:

1. Optionally create a theme Breakpoint Group.
2. Create Image Styles to later use inside of a Responsive Image Style.
3. Create a Responsive Image Style.
4. Create a fieldable entity type.
5. Implement `hook_preprocess_HOOK()`. Optionally add a unique CSS class or ID to template from within `hook_preprocess_HOOK()`. Call method to generate media queries from within `hook_preprocess_HOOK()` and assign return value to `$vars['#attached']['html_head'][]`.

##Detailed description of steps to use this module:

1. Optionally create a theme Breakpoint Group. This will allow you to specify the min-width, max-width and pixel ratios of the media queries instead of using the default ones that come with the Responsive Image module.
2. Create Image Styles to later use as part of a Responsive Image Style. I found it helpful to keep my machine names in the format of "hero_xsmall_2x", "hero_xsmall_1x", "hero_small_2x", "hero_small_1x" etc, but that is up to you.
3. Create a Responsive Image Style. Here you will essentially map each of your Image Styles to a breakpoint from a Breakpoint Group.  I recommend creating a new Responsive Image Style instead of reusing one. This is because this module only supports the "Select a single image style." Type option and not the "Select multiple image styles and use the sizes attribute." option. Reusing an existing Responsive Image Style could be limiting if you need to use the sizes attribute for a non-background responsive image. Optionally you can use your theme Breakpoint Group if you created one.
4. Create a fieldable entity type. I'm going to use Paragraphs as an example from this point on, so I'm going to include some extra steps that wouldn't be necessary if you're using a Node because the image field would be added directly to the Content Type. So, create a Paragraph Type (or use an existing one), and add an Image field or a Media field of type Image. On the Manage Display tab, ensure that your image field is enabled. On a Content Type, add an Entity Reference Revisions field and configure it to reference your Paragraph Type. Go to /node/add and create a new Node of your Content Type and create a new Paragraph of your Paragraph Type through the Entity Reference Revisions field.
5. Implement hook_preprocess_HOOK(). Optionally add a unique CSS class or ID to template from within hook_preprocess_HOOK(). Call method to generate media queries from within hook_preprocess_HOOK() and assign return value to `$vars['#attached']['html_head'][]`. Here is an example, meant to be used inside of `mytheme.theme`:

```
use Drupal\dsjp_responsive_background_image\ResponsiveBackgroundImage;

/**
 * Implements hook_preprocess_HOOK().
 *
 * Preprocess paragraph.html.twig.
 */
function mytheme_preprocess_paragraph(&$vars) {
  $paragraph = $vars['paragraph'];
  $paragraph_type = $paragraph->bundle();

  // Add a unique class based on entity ID to all Paragraphs.
  // Ideally we will have a unique CSS selector with which to generate the media queries.
  // Without a unique selector, you will not be able to have more than one instance
  // of the Hero Paragraph on the same page with different background images on each.
  $paragraph_id = $paragraph->id();
  $css_class = 'paragraph--id--' . $paragraph_id;
  $vars['attributes']['class'] = [$css_class];

  switch ($paragraph_type) {

    // Hero Paragraph.
    case 'hero':
      // Make sure the image field is not empty.
      if (!empty($paragraph->get('field_hero_background_image')
        ->getValue()[0]['target_id'])) {
        
        // Construct a CSS selector string that points to the place a background image
        // will be added. In this example, it is assumed that within my Paragraph template 
        // (paragraph--hero.html.twig) there is a div with the class 'hero__image'.
        $css_selector = $css_class . ' .hero__image';

        // Here we call the method to generate media queries.
        // We pass in the CSS selector string as described above.
        // We pass in the entity object.
        // We pass in the machine name of the image field.
        // We pass in the machine name of the Responsive Image Style. In this example, I have
        // a Responsive Image Style called 'hero_paragraph'.
        $style_tag = ResponsiveBackgroundImage::generateMediaQueries($css_selector, $paragraph, 'field_hero_background_image', 'hero_paragraph');

        // We then check if the media queries were properly generated and attach them to the HTML head.
        if ($style_tag) {
          $vars['#attached']['html_head'][] = $style_tag;
        }
      }

      break;
   }
}
```


You can then view your Node and hopefully you will see a background image where you have specified one with the `$css_selector` argument. Likely you will need to add more CSS background properties within your CSS file, such as `background-size: cover;`. Styling is entirely up to you at this point. This module does nothing more than display the background image and serve the correct image file for the breakpoint or pixel ratio.

##Troubleshooting
* If you are not seeing a background image, first make sure that there is some content inside of the div/element to which you have added the background image. Remember that an empty div will have no height and thus the background image will not be visible.
* You should also inspect the page to ensure that the media queries have been added to the HTML `<head>` as a `<style>` tag. They will look something like this:
```
.paragraph--id--21 .hero__image {
      background-image: url(/sites/default/files/styles/hero_xsmall_1x/public/2018-10/Home.jpg?itok=7_SrQHKQ);
    }
    @media all and (min-width: 0px) and (max-width: 374px) {
      .paragraph--id--21 .hero__image { 
        background-image: url(/sites/default/files/styles/hero_xsmall_1x/public/2018-10/Home.jpg?itok=7_SrQHKQ); 
      }
    }
```
