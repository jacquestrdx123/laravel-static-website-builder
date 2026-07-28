<site_type_blueprint type="personal">
Structural recipe for personal sites. Follow the structure; visuals come from the brief,
style guide, and design_seed. A human being, not a company — first-person voice, intimate
scale (smaller type ceilings, closer spacing than business sites).

## Page skeleton (index.html)

```html
<header class="site-header"><!-- name + tiny nav --></header>
<main id="main">
  <section class="hero" id="top">
    <!-- identity: name, what they do, a personable line; portrait if available -->
    <h1><!-- name --></h1>
    <p data-content="tagline"></p>
  </section>

  <section id="about"><!-- carries the narrative weight: story, values, journey --></section>

  <!-- purpose sections adapt to the person: work/writing/speaking/hire-me.
       offerings (when present) as services with the standard annotation: -->
  <section id="services">
    <ul class="offerings">
      <li data-offering="1">
        <h3 data-field="name"></h3><p data-field="description"></p><span data-field="price"></span>
      </li>
    </ul>
  </section>

  <!-- gallery/testimonials/faq only when requested -->

  <section id="contact"><!-- warm invitation; email
       <a data-content="contact-email" href="mailto:...">; socials if given in the brief --></section>
</main>
<footer><!-- name, email (annotated) --></footer>
```

## Judgement

- Voice is everything: mine the description for how this person talks; keep it first person.
- Photos of the person beat abstract imagery; respect what was actually uploaded.
- Standard css/js architecture (tokens, mobile nav, feature-gated extras, a11y/motion guards).
</site_type_blueprint>
