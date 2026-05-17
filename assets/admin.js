document.addEventListener("DOMContentLoaded", () => {
  const sliders = document.querySelectorAll("[data-lccc-news-slider]");

  sliders.forEach((slider) => {
    const slides = Array.from(slider.querySelectorAll("[data-lccc-news-slide]"));
    const prevButton = slider.querySelector("[data-lccc-news-prev]");
    const nextButton = slider.querySelector("[data-lccc-news-next]");
    const currentCounter = slider.querySelector("[data-lccc-news-current]");

    if (!slides.length) {
      return;
    }

    let currentIndex = 0;

    const showSlide = (nextIndex) => {
      currentIndex = (nextIndex + slides.length) % slides.length;

      slides.forEach((slide, index) => {
        slide.classList.toggle("is-active", index === currentIndex);
      });

      if (currentCounter) {
        currentCounter.textContent = String(currentIndex + 1);
      }
    };

    const goToNext = () => {
      showSlide(currentIndex + 1);
    };

    const goToPrevious = () => {
      showSlide(currentIndex - 1);
    };

    if (nextButton) {
      nextButton.addEventListener("click", goToNext);
    }

    if (prevButton) {
      prevButton.addEventListener("click", goToPrevious);
    }

    if (slides.length > 1) {
      window.setInterval(goToNext, 5000);
    }

    showSlide(0);
  });
});
