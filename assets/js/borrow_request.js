const translateBorrowRequest = window.appTranslate || (key => key);
const cartStorageKey = document.body.dataset.borrowCartKey;
const cartList = document.getElementById('borrowCartItems');
const emptyCartMessage = document.getElementById('borrowCartEmpty');
const hiddenCartItems = document.getElementById('borrowCartHiddenItems');
const cartCount = document.getElementById('borrowCartCount');
const cartForm = document.getElementById('borrowCartForm');
const submitButton = document.getElementById('submitBorrowCart');
const borrowRequestStatus = document.getElementById('borrowCartStatus');
const cartItems = [];
let storageFailed = false;

const setCartStatus = (message) => {
  borrowRequestStatus.textContent = message;
};

const saveCart = () => {
  try {
    localStorage.setItem(cartStorageKey, JSON.stringify(cartItems));
    storageFailed = false;
    return true;
  } catch (error) {
    storageFailed = true;
    setCartStatus(translateBorrowRequest('Could not save your request items in this browser.'));
    return false;
  }
};

const renderCart = () => {
  cartList.replaceChildren();
  hiddenCartItems.replaceChildren();
  emptyCartMessage.hidden = cartItems.length > 0;
  submitButton.disabled = cartItems.length === 0 || storageFailed;

  const totalQuantity = cartItems.reduce((total, item) => total + item.quantity, 0);
  cartCount.textContent = String(totalQuantity);
  cartCount.hidden = totalQuantity === 0;

  cartItems.forEach((item, index) => {
    const row = document.createElement('li');
    row.className = 'borrow-cart-item';

    const details = document.createElement('div');
    details.className = 'borrow-cart-item-details';
    const name = document.createElement('strong');
    name.textContent = item.name;
    const stock = document.createElement('small');
    stock.textContent = translateBorrowRequest('{count} available', { count: item.available });
    details.append(name, stock);

    const controls = document.createElement('div');
    controls.className = 'borrow-cart-item-controls';
    const decrease = document.createElement('button');
    decrease.type = 'button';
    decrease.dataset.cartAction = 'decrease';
    decrease.dataset.index = String(index);
    decrease.setAttribute('aria-label', translateBorrowRequest('Decrease {name} quantity', { name: item.name }));
    decrease.textContent = '−';
    decrease.disabled = item.quantity <= 1;
    const quantity = document.createElement('span');
    quantity.textContent = String(item.quantity);
    const increase = document.createElement('button');
    increase.type = 'button';
    increase.dataset.cartAction = 'increase';
    increase.dataset.index = String(index);
    increase.setAttribute('aria-label', translateBorrowRequest('Increase {name} quantity', { name: item.name }));
    increase.textContent = '+';
    increase.disabled = item.quantity >= item.available;
    const remove = document.createElement('button');
    remove.type = 'button';
    remove.className = 'borrow-cart-remove';
    remove.dataset.cartAction = 'remove';
    remove.dataset.index = String(index);
    remove.textContent = translateBorrowRequest('Remove');
    controls.append(decrease, quantity, increase, remove);
    row.append(details, controls);
    cartList.appendChild(row);

    const equipmentIdInput = document.createElement('input');
    equipmentIdInput.type = 'hidden';
    equipmentIdInput.name = 'equipment_id[]';
    equipmentIdInput.value = String(item.equipmentId);
    const quantityInput = document.createElement('input');
    quantityInput.type = 'hidden';
    quantityInput.name = 'quantity[]';
    quantityInput.value = String(item.quantity);
    hiddenCartItems.append(equipmentIdInput, quantityInput);
  });
};

const isValidCartItem = (item) =>
  item &&
  Number.isSafeInteger(item.equipmentId) &&
  item.equipmentId > 0 &&
  typeof item.name === 'string' &&
  item.name.trim() !== '' &&
  Number.isSafeInteger(item.quantity) &&
  item.quantity > 0 &&
  Number.isSafeInteger(item.available) &&
  item.available > 0;

try {
  if (!cartStorageKey) {
    throw new Error('Missing borrow cart storage key.');
  }

  const pageUrl = new URL(window.location.href);
  if (pageUrl.searchParams.has('submitted')) {
    localStorage.removeItem(cartStorageKey);
    pageUrl.searchParams.delete('submitted');
    window.history.replaceState(null, '', pageUrl);
  } else {
    const savedItems = JSON.parse(localStorage.getItem(cartStorageKey) || '[]');
    if (!Array.isArray(savedItems) || !savedItems.every(isValidCartItem)) {
      throw new Error('Saved borrow cart data is invalid.');
    }
    cartItems.push(...savedItems);
  }
} catch (error) {
  storageFailed = true;
  setCartStatus(translateBorrowRequest('Could not load your saved request items. Return to equipment and add them again.'));
}

cartList.addEventListener('click', (event) => {
  const button = event.target.closest('button[data-cart-action]');
  if (!button) return;
  const item = cartItems[Number(button.dataset.index)];
  if (!item) return;

  if (button.dataset.cartAction === 'increase' && item.quantity < item.available) {
    item.quantity++;
  } else if (button.dataset.cartAction === 'decrease' && item.quantity > 1) {
    item.quantity--;
  } else if (button.dataset.cartAction === 'remove') {
    cartItems.splice(Number(button.dataset.index), 1);
  }

  if (saveCart()) setCartStatus('');
  renderCart();
});

cartForm.addEventListener('submit', (event) => {
  if (cartItems.length === 0 || storageFailed) {
    event.preventDefault();
    setCartStatus(
      storageFailed
        ? translateBorrowRequest('Could not save your request items in this browser.')
        : translateBorrowRequest('Add at least one item before submitting your request.')
    );
  }
});

renderCart();
