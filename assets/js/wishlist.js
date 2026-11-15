document.addEventListener('DOMContentLoaded', () => {
  const categoryFilter = document.getElementById('categoryFilter');
  const toast = document.getElementById('wishlistToast');
  const emptyState = document.getElementById('wishlistEmpty');
  const serviceGrid = document.getElementById('serviceGrid');
  const savedCount = document.getElementById('savedCount');

  let toastTimer = null;

  // 1. Category filtering
  if (categoryFilter) {
    categoryFilter.addEventListener('change', () => {
      applyFilter(categoryFilter.value);
    });
  }

  function applyFilter(category) {
    document.querySelectorAll('[data-card]').forEach((card) => {
      const matches = category === 'all' || card.dataset.category === category;
      card.classList.toggle('wl-hidden', !matches);
    });

    checkEmptyState();
  }

  // 2. Remove from wishlist (heart toggle)
  document.querySelectorAll('.wishlist-heart').forEach((heart) => {
    heart.addEventListener('click', () => {
      const card = heart.closest('[data-card]');
      const serviceId = heart.dataset.serviceId;

      if (!card || !serviceId) return;

      heart.disabled = true;

      fetch('toggle-favorite.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'service_id=' + encodeURIComponent(serviceId),
      })
        .then((response) => {
          if (response.status === 401) {
            window.location.href = 'login.php';
            return null;
          }
          return response.json();
        })
        .then((data) => {
          if (!data) return;

          // On the wishlist page, removing is the only action —
          // once it's unfavorited server-side, fade the card out
          if (!data.favorited) {
            card.classList.add('removing');
            showToast();

            card.addEventListener(
              'transitionend',
              () => {
                card.remove();
                updateSavedCount();
                checkEmptyState();
              },
              { once: true }
            );
          }
        })
        .catch(() => {
          heart.disabled = false;
        });
    });
  });

  function showToast() {
    if (!toast) return;

    toast.classList.add('show');

    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => {
      toast.classList.remove('show');
    }, 2200);
  }

  function updateSavedCount() {
    if (!savedCount || !serviceGrid) return;

    savedCount.textContent = serviceGrid.querySelectorAll('[data-card]').length;
  }

  function checkEmptyState() {
    if (!emptyState) return;

    const visibleCards = document.querySelectorAll('[data-card]:not(.wl-hidden)');
    emptyState.classList.toggle('wl-hidden', visibleCards.length > 0);
  }
});
