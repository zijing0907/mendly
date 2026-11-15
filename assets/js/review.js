const list = document.getElementById('list');
const subcount = document.getElementById('subcount');
const cards = list.querySelectorAll('.card');

// Cards that already had a saved review when the page rendered (server
// marks them with data-submitted="1" — see review.php) start locked.
cards.forEach(card => {
  if (card.dataset.submitted === '1') {
    card.classList.add('submitted');
  }
});

// Recomputes the "awaiting feedback" count from actual card state each time,
// instead of a manually-decremented counter (which let repeat clicks on
// "Submit" keep counting down).
const updateCount = () => {
  const totalCards = list.querySelectorAll('.card').length;
  const remaining = list.querySelectorAll('.card:not(.submitted)').length;

  if (totalCards === 0) {
    subcount.textContent = '';
  } else if (remaining === 0) {
    subcount.textContent = "You're all caught up — thanks for sharing your feedback!";
  } else {
    subcount.textContent = `${remaining} artisan${remaining === 1 ? '' : 's'} awaiting your feedback`;
  }
};
updateCount();

// Star ratings
list.querySelectorAll('.stars').forEach(stars => {
  stars.addEventListener('click', e => {
    if (!e.target.matches('.star')) return;
    const value = +e.target.dataset.value;
    stars.dataset.rating = value;
    [...stars.children].forEach(s => s.classList.toggle('filled', +s.dataset.value <= value));
    stars.closest('.card').querySelector('.rating-error').classList.remove('show');
  });
});

// Tag toggles
list.querySelectorAll('.tags').forEach(group => {
  group.addEventListener('click', e => e.target.matches('.tag') && e.target.classList.toggle('active'));
});

const MAX_MEDIA_BYTES = 15 * 1024 * 1024; // 15MB — keep in sync with submit-review.php

// Builds a thumbnail element for an uploaded image/video File, or for a
// {path, type} object already saved on the server.
// removable=true adds the "x" button (used while editing);
// removable=false is used for the read-only submitted summary.
function fileToThumb(source, { removable = true } = {}) {
  const isFile = source instanceof File;
  const url = isFile ? URL.createObjectURL(source) : source.path;
  const isVideo = isFile ? source.type.startsWith('video/') : source.type === 'video';

  const thumb = document.createElement('div');
  thumb.className = 'thumb';
  if (isFile) thumb.dataset.url = url;

  const mediaWrap = document.createElement('div');
  mediaWrap.className = 'thumb-media';

  const media = document.createElement(isVideo ? 'video' : 'img');
  media.src = url;
  if (isVideo) {
    media.muted = true;
    media.playsInline = true;
  } else {
    media.alt = '';
  }
  mediaWrap.appendChild(media);

  if (isVideo) {
    const playIcon = document.createElement('span');
    playIcon.className = 'play-icon';
    playIcon.innerHTML = '&#9658;';
    mediaWrap.appendChild(playIcon);
  }

  thumb.appendChild(mediaWrap);

  if (removable) {
    const removeBtn = document.createElement('button');
    removeBtn.type = 'button';
    removeBtn.setAttribute('aria-label', 'Remove media');
    removeBtn.innerHTML = '&times;';
    thumb.appendChild(removeBtn);
  }

  return thumb;
}

// Media upload (max 3 per card, real images & videos, with remove).
// Files are only staged client-side here — nothing is uploaded until the
// card's "Submit review" button is clicked (see the submit handler below).
cards.forEach(card => {
  const btn = card.querySelector('.upload-btn');
  const fileInput = card.querySelector('.media-file-input');
  const thumbs = card.querySelector('.media-thumbs');
  const count = card.querySelector('.media-count');
  const mediaError = card.querySelector('.media-error');
  const files = []; // kept in sync with the thumbs currently shown

  if (!btn) return; // already-submitted cards render without the editable form

  const refresh = () => {
    const n = thumbs.children.length;
    count.textContent = `${n}/3 upload media`;
    btn.disabled = n >= 3;
  };

  btn.addEventListener('click', () => {
    if (thumbs.children.length >= 3) return;
    fileInput.click();
  });

  fileInput.addEventListener('change', () => {
    const slotsLeft = 3 - thumbs.children.length;
    const picked = [...fileInput.files].slice(0, slotsLeft);

    let hadOversized = false;
    picked.forEach(file => {
      if (file.size > MAX_MEDIA_BYTES) {
        hadOversized = true; // skip this file, it's over the cap
        return;
      }
      const thumb = fileToThumb(file);
      thumb.querySelector('button').addEventListener('click', () => {
        URL.revokeObjectURL(thumb.dataset.url);
        const idx = files.indexOf(file);
        if (idx > -1) files.splice(idx, 1);
        thumb.remove();
        refresh();
      });
      files.push(file);
      thumbs.appendChild(thumb);
    });

    if (mediaError) mediaError.classList.toggle('show', hadOversized);

    fileInput.value = ''; // allow re-selecting the same file later
    refresh();
  });

  // Exposed so the submit handler below can read the attached files.
  card._getMediaFiles = () => files;
});

// Require rating + review text before submitting.
// On submit, POST everything (rating, tags, text, media) to
// submit-review.php. Once the server confirms it's saved, the card is
// locked: the form is replaced with a read-only "See your review"
// summary built from the server's response, and can no longer be edited.
cards.forEach(card => {
  const stars = card.querySelector('.stars');
  const ratingError = card.querySelector('.rating-error');
  const box = card.querySelector('.review-box');
  const reviewError = card.querySelector('.review-box + .error-msg');
  const submit = card.querySelector('.submit-btn');
  const tagsGroup = card.querySelector('.tags');

  const editable = card.querySelector('.editable');
  const submittedView = card.querySelector('.submitted-view');
  const reviewDate = card.querySelector('.review-date');
  const seeReviewBtn = card.querySelector('.see-review-btn');
  const summary = card.querySelector('.summary');
  const summaryStars = card.querySelector('.summary-stars');
  const summaryTags = card.querySelector('.summary-tags');
  const summaryText = card.querySelector('.summary-text');
  const summaryMedia = card.querySelector('.summary-media');

  if (!submit) return; // already-submitted cards have no submit button to wire up

  box.addEventListener('input', () => {
    if (box.value.trim()) { box.classList.remove('error'); reviewError.classList.remove('show'); }
  });

  submit.addEventListener('click', async () => {
    if (card.classList.contains('submitted')) return; // already locked

    const rating = +stars.dataset.rating;
    const hasRating = rating > 0;
    const hasReview = !!box.value.trim();

    ratingError.classList.toggle('show', !hasRating);
    box.classList.toggle('error', !hasReview);
    reviewError.classList.toggle('show', !hasReview);

    if (!hasRating || !hasReview) {
      if (!hasReview) box.focus();
      return;
    }

    const requestId = card.dataset.requestId;
    const activeTags = [...tagsGroup.querySelectorAll('.tag.active')].map(t => t.textContent);
    const mediaFiles = card._getMediaFiles ? card._getMediaFiles() : [];

    const formData = new FormData();
    formData.append('request_id', requestId);
    formData.append('rating', rating);
    formData.append('comment', box.value.trim());
    activeTags.forEach(tag => formData.append('tags[]', tag));
    mediaFiles.forEach(file => formData.append('media[]', file));

    submit.disabled = true;
    const originalLabel = submit.textContent;
    submit.textContent = 'Submitting...';

    try {
      const res = await fetch('submit-review.php', { method: 'POST', body: formData });
      const data = await res.json().catch(() => ({}));

      if (!res.ok || !data.success) {
        throw new Error(data.error || 'Failed to submit review.');
      }

      // Build the read-only summary from what the server actually saved.
      summaryStars.innerHTML = '';
      for (let n = 1; n <= 5; n++) {
        const s = document.createElement('span');
        s.className = 'star' + (n <= data.rating ? ' filled' : '');
        s.innerHTML = '&#9733;';
        summaryStars.appendChild(s);
      }

      summaryTags.innerHTML = '';
      (data.tags || []).forEach(tag => {
        const chip = document.createElement('span');
        chip.className = 'tag active';
        chip.textContent = tag;
        summaryTags.appendChild(chip);
      });

      summaryText.textContent = data.comment;

      summaryMedia.innerHTML = '';
      (data.media || []).forEach(m => {
        summaryMedia.appendChild(fileToThumb(m, { removable: false }));
      });

      const postedOn = new Date(data.review_date).toLocaleDateString(undefined, {
        year: 'numeric', month: 'short', day: 'numeric'
      });
      reviewDate.textContent = `Reviewed on ${postedOn}`;

      // Lock the card: swap the editable form out for the read-only view
      card.classList.add('submitted');
      card.dataset.submitted = '1';
      editable.hidden = true;
      submittedView.hidden = false;

      updateCount();
    } catch (err) {
      alert(err.message || 'Something went wrong while submitting your review.');
      submit.disabled = false;
      submit.textContent = originalLabel;
    }
  });

  seeReviewBtn.addEventListener('click', () => {
    const willShow = summary.hidden;
    summary.hidden = !willShow;
    seeReviewBtn.textContent = willShow ? 'Hide your review' : 'See your review';
  });
});

// Sticky header shadow on scroll
const header = document.getElementById('header');
window.addEventListener('scroll', () => header.classList.toggle('scrolled', window.scrollY > 4));