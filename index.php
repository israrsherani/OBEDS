<?php
require __DIR__ . '/includes/db.php';

$pageTitle = 'Home';
$base      = '';
$active    = 'home';
$fullWidth = true; // hero poori width me aaye

$counts = ['exchange' => 0, 'donate' => 0];

$rows = $pdo->query(
    "SELECT listing_type, COUNT(*) AS n
     FROM books
     WHERE status = 'Available'
     GROUP BY listing_type"
)->fetchAll();

foreach ($rows as $r) {
    if (isset($counts[$r['listing_type']])) {
        $counts[$r['listing_type']] = (int) $r['n'];
    }
}

require __DIR__ . '/includes/header.php';
?>

<!-- HERO -->
<section class="hero" id="top">
    <div class="wrap hero-in">
        <div>
            <span class="eyebrow">A COMMUNITY BUILT AROUND BOOKS</span>
            <h1>Give your books a <em>second life.</em></h1>
            <p>
                Your old books can become someone else's new opportunity.
                Exchange knowledge, donate books and connect with fellow
                students through OBEDS.
            </p>
            <div class="btns">
                <a class="btn gold" href="books/list_books.php">Explore Books &rarr;</a>
                <a class="btn ghost" href="books/add_book.php">+ List Your Book</a>
            </div>
            <div class="trust">&hearts; Share knowledge. Reduce waste. Help students.</div>
        </div>

        <div class="stack" aria-hidden="true">
            <div class="shelf"></div>
            <div class="bk b1 float">Data Structures</div>
            <div class="bk b2">Database Systems</div>
            <div class="bk b3 float">C++ Primer</div>
            <div class="bk b4">Algorithms</div>
            <div class="bk upright u1">Fiction</div>
            <div class="bk upright u2">Science</div>
        </div>
    </div>
</section>

<!-- STATS -->
<section class="stats">
    <div class="wrap stat-grid two">
        <a class="stat rv" href="books/list_books.php?type=exchange">
            <div class="ico">🔄</div>
            <div>
                <b data-n="<?= (int) $counts['exchange'] ?>"><?= (int) $counts['exchange'] ?></b>
                <span>Available for Exchange</span>
            </div>
        </a>
        <a class="stat rv" href="books/list_books.php?type=donate">
            <div class="ico">🎁</div>
            <div>
                <b data-n="<?= (int) $counts['donate'] ?>"><?= (int) $counts['donate'] ?></b>
                <span>Available for Donation</span>
            </div>
        </a>
    </div>
</section>

<!-- HOW IT WORKS -->
<section class="block">
    <div class="wrap">
        <div class="head center rv">
            <span class="eyebrow">SIMPLE &amp; EASY</span>
            <h2>How OBEDS works</h2>
            <p>Three simple steps to make books accessible to the student community.</p>
        </div>
        <div class="steps">
            <div class="step rv">
                <div class="ico">📖</div>
                <h3>List a Book</h3>
                <p>Add a book you no longer need. Choose whether to exchange it or give it away for free.</p>
            </div>
            <div class="step rv">
                <div class="ico">🔎</div>
                <h3>Discover &amp; Explore</h3>
                <p>Browse the collection, find books by title or category and discover your next learning resource.</p>
            </div>
            <div class="step rv">
                <div class="ico">🤝</div>
                <h3>Request &amp; Connect</h3>
                <p>Send a request to the book owner, wait for approval and track your request through the system.</p>
            </div>
        </div>
    </div>
</section>

<!-- WHY OBEDS -->
<section class="block why">
    <div class="wrap">
        <div class="head center rv">
            <span class="eyebrow">MORE THAN JUST BOOKS</span>
            <h2>Why OBEDS?</h2>
            <p>Small acts of sharing can create meaningful opportunities for students.</p>
        </div>
        <div class="why-grid">
            <div class="why-c rv">
                <div class="ico">🌱</div>
                <h3>Less Waste</h3>
                <p>Give books a second life instead of leaving them unused.</p>
            </div>
            <div class="why-c rv">
                <div class="ico">🎓</div>
                <h3>Student Support</h3>
                <p>Help fellow students access learning materials.</p>
            </div>
            <div class="why-c rv">
                <div class="ico">💡</div>
                <h3>Shared Knowledge</h3>
                <p>Build a culture where learning and generosity go together.</p>
            </div>
        </div>
    </div>
</section>

<!-- FINAL CTA -->
<section class="cta-band">
    <div class="wrap">
        <div class="cta-box rv">
            <div>
                <span class="eyebrow">YOUR NEXT STEP</span>
                <h2>Someone is waiting<br>for your book.</h2>
                <p>Share a book today and help another student continue their learning journey.</p>
                <a class="btn gold" href="books/add_book.php">Share a Book &rarr;</a>
            </div>
            <div class="cta-sym" aria-hidden="true">📚</div>
        </div>
    </div>
</section>

<script>
// scroll reveal + count-up
(function () {
    var items = document.querySelectorAll('.rv');
    function count(el) {
        var t = +el.dataset.n, s = null;
        if (!t) return;
        el.textContent = 0;
        function f(ts) {
            if (!s) s = ts;
            var p = Math.min((ts - s) / 800, 1);
            el.textContent = Math.round(p * t);
            if (p < 1) requestAnimationFrame(f);
        }
        requestAnimationFrame(f);
    }
    if (!('IntersectionObserver' in window)) {
        items.forEach(function (el) { el.classList.add('in'); });
        return;
    }
    var io = new IntersectionObserver(function (es) {
        es.forEach(function (e) {
            if (!e.isIntersecting) return;
            e.target.classList.add('in');
            var n = e.target.querySelector('[data-n]');
            if (n && !n.dataset.done) { n.dataset.done = 1; count(n); }
            io.unobserve(e.target);
        });
    }, { threshold: .2 });
    items.forEach(function (el) { io.observe(el); });
})();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>