const customerCard = document.getElementById("customerCard");
const expertCard = document.getElementById("expertCard");

const selectionMessage =
    document.getElementById("selectionMessage");

customerCard.addEventListener("click", function () {

    customerCard.classList.add("selected");
    expertCard.classList.remove("selected");

    selectionMessage.textContent =
        "Customer account selected.";

    setTimeout(function () {
        window.location.href =
            "pages/register-customer.php";
    }, 700);
});

expertCard.addEventListener("click", function () {

    expertCard.classList.add("selected");
    customerCard.classList.remove("selected");

    selectionMessage.textContent =
        "Repair Expert account selected.";

    setTimeout(function () {
        window.location.href =
            "pages/register-expert.php";
    }, 700);
});