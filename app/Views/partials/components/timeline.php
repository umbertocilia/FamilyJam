<?php $events = $events ?? []; ?>
<div class="timeline">
    <?php foreach ($events as $event): ?>
        <article class="timeline__item">
            <span class="timeline__dot" aria-hidden="true"></span>
            <div class="timeline__content">
                <strong><?= esc((string) ($event['title'] ?? 'Event')) ?></strong>
                <?php if (! empty($event['body'])): ?>
                    <p><?= esc((string) $event['body']) ?></p>
                <?php endif; ?>
                <?php if (! empty($event['meta'])): ?>
                    <small><?= esc((string) $event['meta']) ?></small>
                <?php endif; ?>
            </div>
        </article>
    <?php endforeach; ?>
</div>
