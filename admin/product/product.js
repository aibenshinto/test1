document.addEventListener('DOMContentLoaded', () => {
  const addBtn = document.getElementById('addProductBtn');
  if (addBtn) {
    addBtn.addEventListener('click', () => {
      showAddProductForm();
    });
  }

  // Delegate globally to handle dynamically injected table buttons
  document.addEventListener('click', event => {
    const target = event.target;

    if (target.classList.contains('edit-product-btn')) {
      const id = target.getAttribute('data-id');
      // showEditProductForm(id);
    }

    if (target.classList.contains('delete-btn')) {
      const id = target.getAttribute('data-id');
      deleteProduct(id);
    }
  });
});
function showAddProductForm() {
  const formHtml = `
    <h3>Add New Product</h3>
    <form id="productForm">
      <label>Name:</label><br>
      <input type="text" name="name" required><br><br>
      <label>Price:</label><br>
      <input type="number" name="price" required min="0" step="0.01"><br><br>
      <label>Stock:</label><br>
      <input type="number" name="stock" required min="0"><br><br>
      <button type="submit">Add Product</button>
      <button type="button" onclick="cancelForm()">Cancel</button>
    </form>
  `;

  document.getElementById('mainContent').innerHTML = formHtml;

  document.getElementById('productForm').addEventListener('submit', e => {
    e.preventDefault();
    addProduct(new FormData(e.target));
  });
}

function cancelForm() {
  // Reload the product list
  loadContent('product');
}

function addProduct(formData) {
  fetch('../product/add_product.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    alert(data.message);
    if (data.success) {
      loadContent('product');
    }
  })
  .catch(err => alert('Error: ' + err));
}

function showEditProductForm(id) {
  fetch(`../product/get_product.php?id=${id}`)
    .then(res => res.json())
    .then(product => {
      if (!product) {
        alert('Product not found');
        return;
      }
      const formHtml = `
        <h3>Edit Product</h3>
        <form id="productForm">
          <input type="hidden" name="id" value="${product.id}">
          <label>Name:</label><br>
          <input type="text" name="name" value="${product.name}" required><br><br>
          <label>Price:</label><br>
          <input type="number" name="price" value="${product.price}" required min="0" step="0.01"><br><br>
          <label>Stock:</label><br>
          <input type="number" name="stock" value="${product.stock}" required min="0"><br><br>
          <button type="submit">Update Product</button>
          <button type="button" onclick="cancelForm()">Cancel</button>
        </form>
      `;

      document.getElementById('mainContent').innerHTML = formHtml;

      document.getElementById('productForm').addEventListener('submit', e => {
        e.preventDefault();
        updateProduct(new FormData(e.target));
      });
    })
    .catch(err => alert('Error fetching product: ' + err));
}

function updateProduct(formData) {
  fetch('../product/update_product.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    alert(data.message);
    if (data.success) {
      loadContent('product');
    }
  })
  .catch(err => alert('Error: ' + err));
}

function deleteProduct(id) {
  fetch('../product/delete_product.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `id=${id}`
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      loadContent('product');
      // Optionally show a toast or alert
      // alert('Product deleted successfully!');
    } else {
      console.error(data.message);
    }
  })
  .catch(err => console.error('Error:', err));
}

