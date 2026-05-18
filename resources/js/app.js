const roamingTimers = new WeakMap();

function randomPosition(maxX, maxY) {
	return {
		x: Math.max(0, Math.random() * Math.max(0, maxX)),
		y: Math.max(0, Math.random() * Math.max(0, maxY)),
	};
}

function initRoamingAnimals() {
	const visualWrap = document.querySelector("[data-roaming-wrap]");
	if (visualWrap && visualWrap.querySelectorAll("[data-roaming-animal]").length === 0) {
		const fallback = ["🐻", "🐰", "🦌", "🐱", "🐼", "🦊", "🐯", "🦁", "🐨", "🐸", "🐵", "🐶", "🐹", "🐺", "🦝", "🐷", "🐥", "🦉"];
		fallback.forEach((emoji) => {
			const element = document.createElement("div");
			element.className = "emoji";
			element.setAttribute("data-roaming-animal", "");
			element.textContent = emoji;
			visualWrap.appendChild(element);
		});
	}

	const animals = document.querySelectorAll("[data-roaming-animal]");
	const authCard = document.querySelector(".cp-auth-card");
	const cardPadding = 28;

	let hitboxLayer = document.querySelector("[data-roaming-hitbox-wrap]");
	if (!hitboxLayer) {
		hitboxLayer = document.createElement("div");
		hitboxLayer.className = "cp-roaming-hitbox-layer";
		hitboxLayer.setAttribute("data-roaming-hitbox-wrap", "");
		document.body.appendChild(hitboxLayer);
	}

	hitboxLayer.querySelectorAll("[data-roaming-hitbox-for]").forEach((hitbox) => {
		if (!hitbox._roamingTarget || !hitbox._roamingTarget.isConnected) {
			hitbox.remove();
		}
	});

	const intersects = (a, b) => !(a.right <= b.left || a.left >= b.right || a.bottom <= b.top || a.top >= b.bottom);

	const getForbiddenRect = () => {
		if (!authCard) {
			return null;
		}

		const rect = authCard.getBoundingClientRect();
		return {
			left: Math.max(0, rect.left - cardPadding),
			top: Math.max(0, rect.top - cardPadding),
			right: Math.min(window.innerWidth, rect.right + cardPadding),
			bottom: Math.min(window.innerHeight, rect.bottom + cardPadding),
		};
	};

	const safeRandomPosition = (width, height) => {
		const maxX = Math.max(0, window.innerWidth - width);
		const maxY = Math.max(0, window.innerHeight - height);
		const forbidden = getForbiddenRect();

		if (!forbidden) {
			return randomPosition(maxX, maxY);
		}

		for (let attempt = 0; attempt < 80; attempt += 1) {
			const pos = randomPosition(maxX, maxY);
			const rect = { left: pos.x, top: pos.y, right: pos.x + width, bottom: pos.y + height };
			if (!intersects(rect, forbidden)) {
				return pos;
			}
		}

		const leftSpace = forbidden.left;
		const rightSpace = window.innerWidth - forbidden.right;
		const topSpace = forbidden.top;
		const bottomSpace = window.innerHeight - forbidden.bottom;

		if (leftSpace >= rightSpace && leftSpace >= topSpace && leftSpace >= bottomSpace && leftSpace > width) {
			return { x: Math.random() * Math.max(0, leftSpace - width), y: Math.random() * maxY };
		}

		if (rightSpace >= topSpace && rightSpace >= bottomSpace && rightSpace > width) {
			return {
				x: forbidden.right + Math.random() * Math.max(0, rightSpace - width),
				y: Math.random() * maxY,
			};
		}

		if (topSpace >= bottomSpace && topSpace > height) {
			return { x: Math.random() * maxX, y: Math.random() * Math.max(0, topSpace - height) };
		}

		if (bottomSpace > height) {
			return {
				x: Math.random() * maxX,
				y: forbidden.bottom + Math.random() * Math.max(0, bottomSpace - height),
			};
		}

		return randomPosition(maxX, maxY);
	};

	animals.forEach((animal) => {
		if (animal.dataset.roamingReady === "1" && animal._roamingHitbox && animal._roamingHitbox.isConnected) {
			return;
		}

		animal.dataset.roamingReady = "1";
		animal.style.pointerEvents = "none";

		let isMoving = true;
		let isDragging = false;
		let didDrag = false;
		let offsetX = 0;
		let offsetY = 0;
		let resumeTimeout = null;

		const hitbox = document.createElement("div");
		hitbox.className = "emoji-hit";
		hitbox.setAttribute("aria-hidden", "true");
		hitbox.setAttribute("data-roaming-hitbox-for", animal.textContent || "animal");
		hitbox._roamingTarget = animal;
		hitboxLayer.appendChild(hitbox);
		animal._roamingHitbox = hitbox;

		const syncHitbox = () => {
			const size = animal.getBoundingClientRect();
			hitbox.style.width = `${size.width}px`;
			hitbox.style.height = `${size.height}px`;
			hitbox.style.left = animal.style.left;
			hitbox.style.top = animal.style.top;
		};

		const startAt = () => {
			const size = animal.getBoundingClientRect();
			const pos = safeRandomPosition(size.width, size.height);
			animal.style.left = `${pos.x}px`;
			animal.style.top = `${pos.y}px`;
			syncHitbox();
		};

		const moveRandomly = () => {
			if (!isMoving || isDragging) {
				return;
			}

			const size = animal.getBoundingClientRect();
			const pos = safeRandomPosition(size.width, size.height);
			animal.style.left = `${pos.x}px`;
			animal.style.top = `${pos.y}px`;
			syncHitbox();
		};

		startAt();
		const timer = window.setInterval(moveRandomly, 1200);
		roamingTimers.set(animal, timer);

		const toggleMovement = () => {
			isMoving = !isMoving;
			animal.classList.toggle("caught", !isMoving);
			hitbox.classList.toggle("caught", !isMoving);
		};

		const beginDrag = (event) => {
			if (event.pointerType === "mouse" && event.button !== 0) {
				return;
			}

			isDragging = true;
			didDrag = false;
			isMoving = false;
			animal.classList.add("caught");
			hitbox.classList.add("caught");

			if (resumeTimeout) {
				clearTimeout(resumeTimeout);
				resumeTimeout = null;
			}

			if (hitbox.setPointerCapture) {
				hitbox.setPointerCapture(event.pointerId);
			}

			const rect = animal.getBoundingClientRect();
			offsetX = event.clientX - rect.left;
			offsetY = event.clientY - rect.top;
			event.preventDefault();
		};

		const dragMove = (event) => {
			if (!isDragging) {
				return;
			}

			didDrag = true;

			const rect = animal.getBoundingClientRect();
			const maxX = Math.max(0, window.innerWidth - rect.width);
			const maxY = Math.max(0, window.innerHeight - rect.height);
			const nextX = Math.min(maxX, Math.max(0, event.clientX - offsetX));
			const nextY = Math.min(maxY, Math.max(0, event.clientY - offsetY));

			animal.style.left = `${nextX}px`;
			animal.style.top = `${nextY}px`;
			syncHitbox();
			event.preventDefault();
		};

		const finishDrag = (event) => {
			if (!isDragging) {
				return;
			}

			isDragging = false;
			if (hitbox.hasPointerCapture && hitbox.hasPointerCapture(event.pointerId)) {
				hitbox.releasePointerCapture(event.pointerId);
			}

			resumeTimeout = window.setTimeout(() => {
				isMoving = true;
				animal.classList.remove("caught");
				hitbox.classList.remove("caught");
				resumeTimeout = null;
			}, 1500);
		};

		hitbox.addEventListener("pointerdown", beginDrag);
		hitbox.addEventListener("pointermove", dragMove);
		hitbox.addEventListener("pointerup", finishDrag);
		hitbox.addEventListener("pointercancel", finishDrag);
		hitbox.addEventListener("click", (event) => {
			if (didDrag) {
				didDrag = false;
				event.preventDefault();
				return;
			}

			toggleMovement();
		});
	});
}

document.addEventListener("DOMContentLoaded", initRoamingAnimals);
document.addEventListener("livewire:navigated", initRoamingAnimals);
document.addEventListener("livewire:load", initRoamingAnimals);
document.addEventListener("livewire:update", initRoamingAnimals);
document.addEventListener("livewire:message.processed", initRoamingAnimals);
