const today = new Date(Date.now()).toLocaleString().split(',')[0];

let word

fetch('./words.json')
    .then((response) => response.json())
    .then((json) => word = json[today]);


const COLORS = ["gray", "green", "yellow"];

const randInt = (num) => Math.floor(Math.random() * num);

async function animateBoxes() {
  let i = 60;
  const boxes = document.querySelectorAll(".boxes .box");
  boxes.forEach((box) => {
    box.textContent = "";
  });
  while (i--) {
    const boxI = randInt(5);
    const colorI = randInt(COLORS.length);
    const box = boxes[boxI];
    if (!box.textContent) {
      boxes[boxI].setAttribute("data-color", COLORS[colorI]);
    }
    await sleep(50);
  }
}

async function fillBoxes(word) {
  const boxes = document.querySelectorAll(".boxes .box");
  for (let i = 0; i < 5; i++) {
    const box = boxes[i];
    box.textContent = word.substring(i, i + 1);
    box.setAttribute("data-color", "gray");
    await sleep(400);
  }
}

const sleep = (delay) =>
  new Promise((resolve) => {
    window.setTimeout(() => {
      resolve();
    }, delay);
  });

function onClick(evt) {
  evt.preventDefault();
  const genElt = document.querySelector("#gen");
  const resultElt = document.querySelector("#result");
  const againElt = document.querySelector("#again");
  const msgElt = document.querySelector("#msg");
  genElt.classList.add("hide");
  resultElt.classList.remove("hide");
  msgElt.classList.add("hide");
  againElt.classList.add("hidden");

  animateBoxes();

  // const index = randInt(words.length);
  // const word = words[today];
  // ANSWER = word;

  window.setTimeout(() => {
    fillBoxes(word)
      .then(() => {
        againElt.classList.remove("hidden");
      })
      .catch((err) => {
        console.error(err);
      });
  }, 5000);
}

document.querySelector("#generate").addEventListener("click", onClick, false);

// Copy

document.querySelector("#copy").addEventListener(
  "click",
  (evt) => {
    evt.preventDefault();
    if (!navigator.clipboard || !navigator.clipboard.writeText) {
      alert("Copy not supported by your browser");
    } else {
      const msgElt = document.querySelector("#msg");
      navigator.clipboard.writeText(word).then(
        function () {
          console.log("Async: Copying to clipboard was successful!");
          msgElt.textContent = "Copied";
          msgElt.classList.remove("hide");
          window.setTimeout(() => {
            msgElt.classList.add("hide");
          }, 1500);
        },
        function (err) {
          alert(`Copy error: ${err}`);
        }
      );
    }
  },
  false
);

// Try again

document.querySelector("#reset").addEventListener("click", onClick, false);