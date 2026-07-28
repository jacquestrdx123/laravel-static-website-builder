<site_type_blueprint type="portfolio">
Structural recipe for portfolio sites. Follow the structure; visual identity comes from the
brief, style guide, and design_seed. The work is the site — chrome recedes, imagery dominates.

## Page skeleton (index.html)

```html
<header class="site-header"><!-- minimal: name/wordmark + tiny nav + contact link --></header>
<main id="main">
  <section class="hero" id="top">
    <!-- identity statement: who, what discipline, where. Type-led or one hero work.
         First person when the description suggests an individual. -->
    <h1><!-- name + discipline --></h1>
    <p data-content="tagline"></p>
  </section>

  <section id="gallery"><!-- THE CENTREPIECE, directly after hero -->
    <!-- large, generous crops; varied spans (not a uniform grid unless minimal style);
         meaningful captions from photo content; lightbox when animations toggled -->
  </section>

  <section id="services"><!-- only when offerings exist: commissions/services/prints -->
    <ul class="offerings">
      <li data-offering="1">
        <h3 data-field="name"></h3><p data-field="description"></p><span data-field="price"></span>
      </li>
    </ul>
  </section>

  <section id="about">
    <!-- personal narrative, first person, one portrait if available -->
  </section>

  <section id="contact">
    <!-- simple and direct: email <a data-content="contact-email" href="mailto:...">,
         availability line; form only when contact_form toggled -->
  </section>
</main>
<footer><!-- name, email (annotated), small print --></footer>
```

## Judgement

- Imagery gets the space; copy is short and confident. No decorative noise around the work.
- Standard css/js architecture (tokens, mobile nav, feature-gated extras, a11y/motion guards).
- Captions/alt text describe actual photo content — study the images.
</site_type_blueprint>
