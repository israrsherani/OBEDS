<?php if (can_delete_book($b['ownerId'])): ?>

    <form method="POST"
          action="delete_book.php"
          onsubmit="return confirm('Are you sure you want to delete this book?');"
          style="margin-top:10px;">

        <input type="hidden"
               name="book_id"
               value="<?= (int)$b['bookId'] ?>">

        <button class="btn danger" type="submit">
            Delete
        </button>

    </form>

<?php endif; ?>