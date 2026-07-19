<site_type_blueprint type="event">
Structural recipe for event sites. Follow the structure; visuals come from the brief, style
guide, and design_seed. The first viewport must answer: WHAT is it, WHEN, WHERE.

## Page skeleton (index.html)

```html
<header class="site-header"><!-- event name + nav + RSVP CTA --></header>
<main id="main">
  <section class="hero" id="top">
    <h1><!-- event name --></h1>
    <p data-content="tagline"></p>
    <!-- DATE + VENUE prominently in the hero, always. Countdown only when animations
         toggled (script.js, graceful without JS). -->
    <a class="cta" href="#contact"><!-- "RSVP" / "Get tickets" --></a>
  </section>

  <section id="about"><!-- what to expect, who it's for, the story of the event --></section>

  <section id="schedule"><!-- when the description implies one: scannable time blocks -->
  </section>

  <!-- offerings when present = tickets/packages, exact names & prices: -->
  <section id="services">
    <ul class="offerings">
      <li data-offering="1">
        <h3 data-field="name"></h3><p data-field="description"></p><span data-field="price"></span>
      </li>
    </ul>
  </section>

  <!-- gallery (past events / venue) when requested -->

  <section id="contact"><!-- venue with plain-text directions, date repeated, RSVP path:
       form when contact_form toggled, else mailto
       <a data-content="contact-email" href="mailto:..."> --></section>
</main>
<footer><!-- event name, date, email (annotated) --></footer>
```

## Judgement

- Design energy follows the event's character (celebration vs conference) via the style guide.
- Date/venue appear at least twice (hero + contact). Never bury the RSVP action.
- Standard css/js architecture; countdown respects prefers-reduced-motion.
</site_type_blueprint>
