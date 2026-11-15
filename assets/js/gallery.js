document.addEventListener('DOMContentLoaded', () => {
  const mainImage = document.querySelector('.main-image');
  const previousButton = document.querySelector('.gallery-control.previous');
  const nextButton = document.querySelector('.gallery-control.next');
  const thumbnails = document.querySelectorAll('.thumbnail');

  if (!mainImage || thumbnails.length === 0) {
    return;
  }

  const images = Array.from(thumbnails).map((thumbnail) => ({
    src: thumbnail.src,
    alt: thumbnail.alt,
  }));

  let currentIndex = 0;

  function showImage(index) {
    if (images.length === 0) {
      return;
    }

    currentIndex = (index + images.length) % images.length;

    mainImage.src = images[currentIndex].src;
    mainImage.alt = images[currentIndex].alt;

    thumbnails.forEach((thumbnail, thumbnailIndex) => {
      thumbnail.classList.toggle('active', thumbnailIndex === currentIndex);
    });
  }

  previousButton?.addEventListener('click', () => {
    showImage(currentIndex - 1);
  });

  nextButton?.addEventListener('click', () => {
    showImage(currentIndex + 1);
  });

  thumbnails.forEach((thumbnail, index) => {
    thumbnail.addEventListener('click', () => {
      showImage(index);
    });
  });

  showImage(0);
});
