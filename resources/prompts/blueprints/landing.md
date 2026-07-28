<site_type_blueprint type="landing">
Structural recipe for single-goal landing pages. Follow the structure; visuals come from the
brief, style guide, and design_seed. Distill ONE conversion action from the description and
drive every section toward it.

## Page skeleton (index.html)

```html
<header class="site-header"><!-- logo + single CTA button, minimal or no nav --></header>
<main id="main">
  <section class="hero" id="top">
    <h1><!-- the promise: outcome-focused headline --></h1>
    <p data-content="tagline"></p>
    <a class="cta" href="#contact"><!-- THE action --></a>
    <!-- trust line under CTA: proof point from the brief -->
  </section>

  <section id="benefits"><!-- 3-4 benefits, outcome-phrased, from the description -->
  </section>

  <section id="offer"><!-- when offerings exist: what you get, exact names/prices -->
    <ul class="offerings">
      <li data-offering="1">
        <h3 data-field="name"></h3><p data-field="description"></p><span data-field="price"></span>
      </li>
    </ul>
  </section>

  <!-- social proof (testimonials) when requested; objection-handling FAQ when requested -->

  <section id="contact"><!-- closing CTA band: restate promise + the action;
       form when contact_form toggled, else mailto CTA with
       <a data-content="contact-email" href="mailto:..."> --></section>
</main>
<footer><!-- minimal: name, email (annotated), small print --></footer>
```

## Judgement

- Repeat the CTA at every natural decision point (hero, after benefits, closing band).
- Cut anything that does not serve the conversion. No generic nav trips away from the goal.
- Standard css/js architecture; scroll-reveal only when animations toggled.
</site_type_blueprint>
