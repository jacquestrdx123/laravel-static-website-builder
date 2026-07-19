<site_type_blueprint type="restaurant">
This is a PROVEN STRUCTURAL RECIPE for restaurant/café/food sites. Follow its page structure,
section order, and annotation patterns. It is NOT a visual design: typography, palette,
spacing, imagery treatment, and personality come from the brief, the style guide, and the
design_seed. Two restaurants built from this blueprint must feel like different rooms.

## Page skeleton (index.html)

```html
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><!-- Name — cuisine, neighbourhood/city --></title>
  <!-- when seo_meta: meta description + og tags; og:image = best food photo -->
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <a class="skip-link" href="#main">Skip to content</a>

  <header class="site-header">
    <a class="logo" href="#top"><!-- restaurant name --></a>
    <nav aria-label="Primary"><!-- Menu, About, Gallery?, Find us --></nav>
    <button class="nav-toggle" aria-expanded="false" aria-controls="site-nav"></button>
    <a class="header-cta" href="#contact"><!-- "Book a table" / "Find us" --></a>
  </header>

  <main id="main">
    <section class="hero" id="top" aria-labelledby="hero-heading">
      <!-- appetite first: most atmospheric food/room photo, full-bleed, scrim for contrast -->
      <h1 id="hero-heading"><!-- name or a sensory line --></h1>
      <p data-content="tagline"></p>
      <!-- ESSENTIALS BAND: hours + location + phone/email visible in or right under the
           hero — a hungry visitor must answer "open now? where?" without scrolling -->
      <a class="cta" href="#menu"><!-- "See the menu" --></a>
    </section>

    <section id="menu" aria-labelledby="menu-heading">
      <h2 id="menu-heading"><!-- offering_label or "Menu" --></h2>
      <!-- group items sensibly when names imply courses/drinks; classic menu typography:
           name left, price right (dot-leader or space-between), description under name -->
      <ul class="menu-list">
        <li data-offering="1">
          <h3 data-field="name"></h3>
          <p data-field="description"></p>
          <span class="price" data-field="price"></span>
        </li>
        <!-- identical structure for every item, all siblings of one parent -->
      </ul>
      <!-- optional dietary/CTA note after the list -->
    </section>

    <section id="about" aria-labelledby="about-heading">
      <h2 id="about-heading"></h2>
      <!-- the story: chef, family, tradition, sourcing — mine the description; pair with
           a people/room photo, not food -->
    </section>

    <!-- gallery when requested: food + room shots, generous crops -->
    <!-- testimonials/faq only when requested -->

    <section id="contact" aria-labelledby="contact-heading">
      <h2 id="contact-heading"><!-- "Find us" / "Book a table" --></h2>
      <!-- hours repeated in full, address as plain text (copyable), email:
           <a data-content="contact-email" href="mailto:...">...</a>
           contact_form feature: short booking-enquiry form (name/email/message) -->
    </section>
  </main>

  <footer><!-- name, hours one-liner, email (annotated), small print --></footer>

  <script src="script.js" defer></script>
</body>
</html>
```

## styles.css shape

Same architecture as every site (tokens → base → layout → header/nav → sections → a11y/motion
guards), plus restaurant specifics:

- Menu typography is the craft moment: align prices consistently, keep item names scannable,
  give groups clear headings. Never card-grid a classic menu unless the style is playful/bold.
- Food photography full-bleed or generously cropped; never tiny thumbnails.
- The hours/location band must remain legible over imagery (scrim or solid band).

## script.js shape

- Standard base (nav toggle, feature-gated extras).
- Gallery lightbox only when gallery requested AND animations toggled: accessible dialog,
  Escape/backdrop close, focus return.

## Restaurant-specific judgement

- Hours, location, and a booking/contact path are the conversion — impossible to miss, twice.
- Sensory copy: name dishes and techniques from the brief; never generic "delicious food".
- Prices are sacred: exact strings from offerings, never reformatted or converted.
</site_type_blueprint>
