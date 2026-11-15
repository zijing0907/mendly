document.addEventListener('DOMContentLoaded', () => {
  const favoriteButtons = document.querySelectorAll('.wishlist-heart, .favorite-btn');

  favoriteButtons.forEach((button) => {
    button.addEventListener('click', async () => {
      const serviceId = button.dataset.serviceId;

      if (!serviceId) {
        return;
      }

      button.disabled = true;

      try {
        const response = await fetch('toggle-favorite.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: 'service_id=' + encodeURIComponent(serviceId),
        });

        if (response.status === 401) {
          window.location.href = 'login.php';
          return;
        }

        if (!response.ok) {
          throw new Error('Failed to update favorite.');
        }

        const data = await response.json();

        if (!data) {
          return;
        }

        const isFavorited = Boolean(data.favorited);

        if (button.classList.contains('wishlist-heart')) {
          button.classList.toggle('active', isFavorited);

          button.textContent = isFavorited ? '♥' : '♡';

          button.setAttribute('aria-pressed', isFavorited ? 'true' : 'false');

          button.setAttribute(
            'aria-label',
            isFavorited ? 'Remove from favorites' : 'Save to favorites'
          );
        }

        if (button.classList.contains('favorite-btn')) {
          button.classList.toggle('active', isFavorited);

          button.innerHTML = isFavorited
            ? '&#9829; Saved to Favorites'
            : '&#9825; Save to Favorites';

          button.setAttribute('aria-pressed', isFavorited ? 'true' : 'false');

          button.setAttribute(
            'aria-label',
            isFavorited ? 'Remove from favorites' : 'Save to favorites'
          );
        }
      } catch (error) {
        console.error('Favorite error:', error);
      } finally {
        button.disabled = false;
      }
    });
  });
});
