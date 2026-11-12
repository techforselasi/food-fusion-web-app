<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'FoodFusion | About us';
$bodyClass = 'page-about';

require __DIR__ . '/includes/header.php';
?>

<div class="about-page">
  <section class="page-hero page-hero--about page-hero--compact" aria-labelledby="about-page-title">
    <div class="wrap about-page__wrap">
      <header class="about-hero">
        <h1 class="page-title" id="about-page-title">About FoodFusion</h1>
        <p class="page-lead">
          We believe food is a language everyone speaks—and the best meals borrow generously from the world.
        </p>
      </header>
    </div>
  </section>

  <section class="section about-section about-philosophy" aria-labelledby="about-philosophy-heading">
    <div class="wrap about-page__wrap">
      <div class="about-philosophy__grid">
        <div class="about-philosophy__copy prose">
          <h2 class="about-section__heading" id="about-philosophy-heading">Our culinary philosophy</h2>
          <p>
            FoodFusion started from a simple idea: respect classic techniques while making room for new ingredients, diaspora stories, and
            the way real people cook at home. We celebrate recipes that travel across borders, adapt to what is in season, and bring people
            together around the table.
          </p>
          <p>
            Whether you are perfecting a family staple or experimenting for the first time, we want this site to feel welcoming, practical,
            and full of flavour.
          </p>
        </div>
        <figure class="about-philosophy__figure accent-panel accent-panel--photo">
          <img
            class="accent-panel__photo"
            src="assets/about-philosophy-chef.png"
            alt="Chef plating dishes in a professional kitchen."
            width="900"
            height="1200"
            decoding="async"
            loading="lazy"
          />
          <div class="accent-panel__scrim" aria-hidden="true"></div>
          <figcaption class="accent-panel__quote">&ldquo;Cook with curiosity. Plate with pride.&rdquo;</figcaption>
        </figure>
      </div>
    </div>
  </section>

  <section class="section section--tint about-section about-values" aria-labelledby="about-values-heading">
    <div class="wrap about-page__wrap">
      <header class="about-section__header">
        <h2 class="section__title" id="about-values-heading">Values</h2>
        <p class="about-section__kicker">What guides how we build and curate FoodFusion.</p>
      </header>
      <ul class="values-grid about-values__grid">
        <li class="value-card">
          <h3 class="value-card__title">Inclusivity</h3>
          <p class="value-card__text">Cuisines and diets deserve equal space. We design content so more eaters can find a place here.</p>
        </li>
        <li class="value-card">
          <h3 class="value-card__title">Education</h3>
          <p class="value-card__text">Clear instructions, honest difficulty labels, and resources that build confidence—not intimidation.</p>
        </li>
        <li class="value-card">
          <h3 class="value-card__title">Community</h3>
          <p class="value-card__text">The cookbook grows when members share. We moderate with care and credit cooks for their creations.</p>
        </li>
        <li class="value-card">
          <h3 class="value-card__title">Sustainability</h3>
          <p class="value-card__text">We highlight ideas that reduce waste and make the most of local produce where possible.</p>
        </li>
      </ul>
    </div>
  </section>

  <section class="section about-section about-team" aria-labelledby="about-team-heading">
    <div class="wrap about-page__wrap">
      <header class="about-section__header">
        <h2 class="section__title" id="about-team-heading">Team</h2>
        <p class="section__intro about-section__intro">
          FoodFusion is developed as a university project by a small student team passionate about UX, data, and good food.
        </p>
      </header>
      <ul class="team-grid about-team__grid">
        <li class="team-card">
          <div class="team-card__avatar" aria-hidden="true">DF</div>
          <h3 class="team-card__name">Leslie Selasi— Product &amp; backend</h3>
          <p class="team-card__role">Leslie — Secure authentication, database design, and PHP application structure.</p>
        </li>
        <li class="team-card">
          <div class="team-card__avatar" aria-hidden="true">CX</div>
          <h3 class="team-card__name">Selasi Junior — Content &amp; culinary</h3>
          <p class="team-card__role">Leslie — Recipe curation, editorial tone, and educational resource selection.</p>
        </li>
        <li class="team-card">
          <div class="team-card__avatar" aria-hidden="true">UX</div>
          <h3 class="team-card__name">Selasi Kofi — Design &amp; accessibility</h3>
          <p class="team-card__role">Leslie — Brand palette, responsive layout, and inclusive interface patterns.</p>
        </li>
      </ul>
    </div>
  </section>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
