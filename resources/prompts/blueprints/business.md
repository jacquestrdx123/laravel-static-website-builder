<site_type_blueprint type="business">
This is a PROVEN STRUCTURAL RECIPE for business sites. Follow its page structure, section
order, and annotation patterns. It is NOT a visual design: typography, palette, spacing,
imagery treatment, and personality come from the brief, the style guide, and the design_seed.
Two business sites built from this blueprint must look like different designers made them.

## Page skeleton (index.html)

```html
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><!-- Business Name — what they do, where --></title>
  <!-- when seo_meta: meta description (150-160 chars), og:title/description/type/image -->
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <a class="skip-link" href="#main">Skip to content</a>

  <header class="site-header"><!-- sticky when sticky_header feature is on -->
    <a class="logo" href="#top"><!-- business name; wordmark treatment from style guide --></a>
    <nav aria-label="Primary">
      <!-- one link per section that exists; no dead links -->
    </nav>
    <button class="nav-toggle" aria-expanded="false" aria-controls="site-nav"><!-- hamburger --></button>
    <a class="header-cta" href="#contact"><!-- short action phrase --></a>
  </header>

  <main id="main">
    <section class="hero" id="top" aria-labelledby="hero-heading">
      <!-- strongest photo as backdrop or split layout; gradient scrim for text contrast -->
      <h1 id="hero-heading"><!-- value proposition, not just the name --></h1>
      <p data-content="tagline"><!-- tagline --></p>
      <a class="cta" href="#contact"><!-- primary action --></a>
    </section>

    <section id="about" aria-labelledby="about-heading">
      <h2 id="about-heading"><!-- warm, specific heading --></h2>
      <!-- story from the description; pair with a photo; one pull-quote or highlight line -->
    </section>

    <section id="services" aria-labelledby="services-heading">
      <h2 id="services-heading"><!-- offering_label or a natural title --></h2>
      <ul class="offerings">
        <!-- one <li> per offering; ALL items identical structure, siblings of one parent -->
        <li data-offering="1">
          <!-- optional <img data-field="image" src="assets/..."> when a product photo fits -->
          <h3 data-field="name"></h3>
          <p data-field="description"></p>
          <span data-field="price"></span><!-- keep element even when price empty -->
        </li>
      </ul>
    </section>

    <!-- gallery / testimonials / pricing / faq sections only when requested, in that order -->

    <section id="contact" aria-labelledby="contact-heading">
      <h2 id="contact-heading"><!-- inviting heading --></h2>
      <!-- hours/location when known; email as:
           <a data-content="contact-email" href="mailto:...">...</a>
           contact_form feature: name/email/message form with mailto fallback -->
    </section>
  </main>

  <footer>
    <!-- business name, contact email (annotated), nav echo, small print -->
  </footer>

  <script src="script.js" defer></script>
</body>
</html>
```

## styles.css shape

1. `:root` design tokens (ink, bg, surface, accent + hover variant, muted, radius, shadow,
   spacing scale, type scale). Dark/auto schemes per color_schemes rules.
2. Reset-lite + base typography (measure 60-75ch, fluid display sizes via clamp).
3. Layout primitives (.container, section padding rhythm via clamp).
4. Header + mobile nav (collapses below ~800px; .nav-toggle shown, panel hidden until open).
5. Per-section styles, varying layout rhythm between sections.
6. `.skip-link` visually hidden until focused; `:focus-visible` styles; `@media (prefers-reduced-motion: reduce)` guards.

## script.js shape

- DOMContentLoaded guard; every feature checks its elements exist before wiring.
- Mobile nav toggle (aria-expanded sync, close on link click + Escape).
- Only the toggled features (smooth_scroll / animations via IntersectionObserver /
  back_to_top / sticky header scrolled-state / contact form validation). No dead code.

## Business-specific judgement

- Trust is the currency: surface years in business, locality, specialties from the brief.
- Header CTA and contact must be reachable from every scroll position (header + footer).
- If offerings exist, services is the visual centrepiece after the hero.
</site_type_blueprint>
