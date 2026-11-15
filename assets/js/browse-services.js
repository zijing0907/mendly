document.addEventListener('DOMContentLoaded', () => {
  // =========================
  // Category Filter
  // =========================
  const categoryCheckboxes = document.querySelectorAll('input[name="category[]"]');

  const allCategories = document.querySelector('input[name="category_all"]');

  if (allCategories) {
    // When a normal category is selected
    categoryCheckboxes.forEach((checkbox) => {
      checkbox.addEventListener('change', () => {
        if (checkbox.checked) {
          // Selecting any category automatically
          // unchecks "All categories"
          allCategories.checked = false;
        }

        // If no category is selected,
        // automatically select "All categories"
        const anySelected = Array.from(categoryCheckboxes).some((item) => item.checked);

        if (!anySelected) {
          allCategories.checked = true;
        }
      });
    });

    // When "All categories" is selected
    allCategories.addEventListener('change', () => {
      if (allCategories.checked) {
        // Clear all individual category selections
        categoryCheckboxes.forEach((checkbox) => {
          checkbox.checked = false;
        });
      } else {
        // Prevent the filter from having no selection
        const anySelected = Array.from(categoryCheckboxes).some((item) => item.checked);

        if (!anySelected) {
          allCategories.checked = true;
        }
      }
    });
  }
});
