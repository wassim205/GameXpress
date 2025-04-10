import React, { useState, useEffect, useContext } from "react";
import { Link, useNavigate } from "react-router-dom";
import { motion, AnimatePresence } from "framer-motion";
import { AuthContext } from "../context/AuthContext";
import api from "../services/apiService";
import { toast } from "./NotificationProvider";
import {
  Plus,
  Search,
  Edit,
  Trash2,
  Image as ImageIcon,
  Eye,
  ChevronLeft,
  ChevronRight,
  Filter,
  ArrowUpDown,
  X,
  Check,
  Loader,
} from "lucide-react";
import { useDropzone } from "react-dropzone";

// Modal component for reuse
const Modal = ({ isOpen, onClose, title, children }) => {
  return (
    <AnimatePresence>
      {isOpen && (
        <motion.div
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          exit={{ opacity: 0 }}
          className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-50"
          onClick={onClose}
        >
          <motion.div
            initial={{ scale: 0.9, opacity: 0 }}
            animate={{ scale: 1, opacity: 1 }}
            exit={{ scale: 0.9, opacity: 0 }}
            className="bg-white rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-hidden"
            onClick={(e) => e.stopPropagation()}
          >
            <div className="flex justify-between items-center p-6 border-b border-gray-200">
              <h3 className="text-xl font-bold text-gray-800">{title}</h3>
              <button
                onClick={onClose}
                className="p-1 rounded-full hover:bg-gray-200 transition-colors"
              >
                <X size={20} />
              </button>
            </div>
            <div className="p-6 overflow-y-auto max-h-[calc(90vh-120px)]">
              {children}
            </div>
          </motion.div>
        </motion.div>
      )}
    </AnimatePresence>
  );
};

// ImageUploader component
const ImageUploader = ({ onUpload, initialImages = [] }) => {
  const [files, setFiles] = useState([]);
  const [uploading, setUploading] = useState(false);
  const [existingImages, setExistingImages] = useState(initialImages);

  const { getRootProps, getInputProps } = useDropzone({
    accept: {
      "image/*": [".png", ".jpg", ".jpeg", ".webp"],
    },
    onDrop: (acceptedFiles) => {
      setFiles([
        ...files,
        ...acceptedFiles.map((file) =>
          Object.assign(file, {
            preview: URL.createObjectURL(file),
          })
        ),
      ]);
    },
  });

  const handleUpload = async () => {
    if (files.length === 0) return;

    setUploading(true);
    const formData = new FormData();

    files.forEach((file, index) => {
      formData.append(`images[${index}]`, file);
    });

    try {
      const result = await onUpload(formData);
      setExistingImages([...existingImages, ...result]);
      setFiles([]);
      toast.success("Images uploaded successfully");
    } catch (error) {
      toast.error("Failed to upload images");
    } finally {
      setUploading(false);
    }
  };

  const removeFile = (index) => {
    setFiles(files.filter((_, i) => i !== index));
  };

  const removeExistingImage = async (imageId) => {
    try {
      await api.delete(`v1/admin/product-images/${imageId}`);
      setExistingImages(existingImages.filter((img) => img.id !== imageId));
      toast.success("Image removed successfully");
    } catch (error) {
      toast.error("Failed to remove image");
    }
  };

  return (
    <div>
      <div
        {...getRootProps()}
        className="border-2 border-dashed border-gray-300 rounded-lg p-6 mb-4 text-center cursor-pointer hover:bg-gray-50 transition-colors"
      >
        <input {...getInputProps()} />
        <ImageIcon className="h-10 w-10 mx-auto text-gray-400 mb-2" />
        <p className="text-gray-600">
          Drag & drop images here, or click to select files
        </p>
        <p className="text-xs text-gray-500 mt-1">
          PNG, JPG, JPEG or WebP (max 5MB)
        </p>
      </div>

      {/* Preview of files to be uploaded */}
      {files.length > 0 && (
        <div className="mb-4">
          <h4 className="text-sm font-medium text-gray-700 mb-2">
            Preview ({files.length})
          </h4>
          <div className="grid grid-cols-4 gap-4">
            {files.map((file, index) => (
              <div key={index} className="relative group">
                <img
                  src={file.preview}
                  alt={`Preview ${index}`}
                  className="h-24 w-24 object-cover rounded-lg border border-gray-200"
                />
                <button
                  onClick={() => removeFile(index)}
                  className="absolute top-1 right-1 p-1 bg-red-500 text-white rounded-full opacity-0 group-hover:opacity-100 transition-opacity"
                >
                  <X size={12} />
                </button>
                <p className="text-xs truncate mt-1">{file.name}</p>
              </div>
            ))}
          </div>

          <button
            onClick={handleUpload}
            disabled={uploading}
            className="mt-4 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors disabled:bg-indigo-300 flex items-center"
          >
            {uploading ? (
              <Loader size={16} className="animate-spin mr-2" />
            ) : null}
            {uploading ? "Uploading..." : "Upload Selected Images"}
          </button>
        </div>
      )}

      {/* Existing images */}
      {existingImages.length > 0 && (
        <div>
          <h4 className="text-sm font-medium text-gray-700 mb-2">
            Product Images ({existingImages.length})
          </h4>
          <div className="grid grid-cols-4 gap-4">
            {existingImages.map((image) => (
              <div key={image.id} className="relative group">
                <img
                  src={image.url}
                  alt={`Product Image ${image.id}`}
                  className="h-24 w-24 object-cover rounded-lg border border-gray-200"
                />
                <button
                  onClick={() => removeExistingImage(image.id)}
                  className="absolute top-1 right-1 p-1 bg-red-500 text-white rounded-full opacity-0 group-hover:opacity-100 transition-opacity"
                >
                  <X size={12} />
                </button>
                {image.is_main && (
                  <div className="absolute bottom-1 left-1 px-2 py-1 bg-green-500 text-white text-xs rounded-lg">
                    Main
                  </div>
                )}
                {!image.is_main && (
                  <button
                    onClick={async () => {
                      try {
                        await api.patch(
                          `v1/admin/product-images/${image.id}/set-main`
                        );
                        // Update the UI
                        setExistingImages(
                          existingImages.map((img) => ({
                            ...img,
                            is_main: img.id === image.id,
                          }))
                        );
                        toast.success("Main image updated");
                      } catch (error) {
                        toast.error("Failed to update main image");
                      }
                    }}
                    className="absolute bottom-1 left-1 px-2 py-1 bg-gray-500 text-white text-xs rounded-lg opacity-0 group-hover:opacity-100 transition-opacity"
                  >
                    Set Main
                  </button>
                )}
              </div>
            ))}
          </div>
        </div>
      )}
    </div>
  );
};

// Main Products Component
const Products = () => {
  const [products, setProducts] = useState([]);
  const [categories, setCategories] = useState([]);
  const [loading, setLoading] = useState(true);
  const [formMode, setFormMode] = useState(null);
  const [formModalOpen, setFormModalOpen] = useState(false);
  const [deleteModalOpen, setDeleteModalOpen] = useState(false);
  const [imagesModalOpen, setImagesModalOpen] = useState(false);
  const [currentProduct, setCurrentProduct] = useState(null);
  const [formData, setFormData] = useState({
    name: "",
    description: "",
    price: "",
    category_id: "",
    stock: "",
    sku: "",
    status: "active",
    featured: false,
  });
  const [searchQuery, setSearchQuery] = useState("");
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [sortField, setSortField] = useState("created_at");
  const [sortDirection, setSortDirection] = useState("desc");
  const [filterCategory, setFilterCategory] = useState("");
  const [filterStatus, setFilterStatus] = useState("");
  const [formErrors, setFormErrors] = useState({});

  const navigate = useNavigate();
  const { user } = useContext(AuthContext);

  // Initial data loading
  useEffect(() => {
    fetchProducts();
    fetchCategories();
  }, [
    currentPage,
    sortField,
    sortDirection,
    filterCategory,
    filterStatus,
    searchQuery,
  ]);

  const fetchProducts = async () => {
    setLoading(true);
    try {
      const params = {
        page: currentPage,
        sort_by: sortField,
        sort_direction: sortDirection,
        category_id: filterCategory,
        status: filterStatus,
        search: searchQuery,
      };

      const response = await api.get("v1/admin/products", { params });
      // console.log("API Response:", response); // Log the API response

      const products = response.data.data.data ?? [];
      // console.log(products);

      const totalPages = response.data.meta.total || 1;
      const CurrentPage = response.data.meta.current_page || 1;
      // const lastPage = response.meta.last_page || 1;

      setProducts(products);
      setCurrentPage(CurrentPage);
      setTotalPages(totalPages);
      // setSortField(lastPage);
    } catch (error) {
      console.log(error);
      
      toast.error("Failed to fetch products");
      setProducts([]);
    } finally {
      setLoading(false);
    }
  };

  const fetchCategories = async () => {
    try {
      const response = await api.get("v1/admin/categories");
      setCategories(response.data.categories);
    } catch (error) {
      toast.error("Failed to fetch categories");
    }
  };

  const handleInputChange = (e) => {
    const { name, value, type, checked } = e.target;
    setFormData({
      ...formData,
      [name]: type === "checkbox" ? checked : value,
    });

    if (formErrors[name]) {
      setFormErrors({
        ...formErrors,
        [name]: "",
      });
    }
  };

  const validateForm = () => {
    const errors = {};
    if (!formData.name) errors.name = "Product name is required";
    if (!formData.price) errors.price = "Price is required";
    else if (isNaN(formData.price) || parseFloat(formData.price) <= 0)
      errors.price = "Price must be a positive number";
    if (!formData.category_id) errors.category_id = "Category is required";
    if (formData.stock && isNaN(formData.stock))
      errors.stock = "Stock must be a number";

    setFormErrors(errors);
    return Object.keys(errors).length === 0;
  };

  const openCreateForm = () => {
    setFormData({
      name: "",
      description: "",
      price: "",
      category_id: "",
      stock: "",
      sku: "",
      status: "active",
      featured: false,
    });
    setFormErrors({});
    setFormMode("create");
    setFormModalOpen(true);
  };

  const openEditForm = (product) => {
    setCurrentProduct(product);
    setFormData({
      name: product.name,
      description: product.description || "",
      price: product.price.toString(),
      category_id: product.category_id.toString(),
      stock: product.stock ? product.stock.toString() : "",
      sku: product.sku || "",
      status: product.status || "active",
      featured: product.featured || false,
    });
    setFormErrors({});
    setFormMode("edit");
    setFormModalOpen(true);
  };

  const openDeleteModal = (product) => {
    setCurrentProduct(product);
    setDeleteModalOpen(true);
  };

  const openImagesModal = (product) => {
    setCurrentProduct(product);
    setImagesModalOpen(true);
  };

  // CRUD operations
  const handleCreateProduct = async () => {
    if (!validateForm()) return;

    try {
      const response = await api.post("v1/admin/products", formData);
      toast.success("Product created successfully");
      setFormModalOpen(false);
      fetchProducts();

      // Optionally open the image uploader for the new product
      setCurrentProduct(response.data.data);
      setImagesModalOpen(true);
    } catch (error) {
      if (error.response && error.response.data.errors) {
        setFormErrors(error.response.data.errors);
      } else {
        toast.error("Failed to create product");
      }
    }
  };

  const handleUpdateProduct = async () => {
    if (!validateForm()) return;

    try {
      await api.put(`v1/admin/products/${currentProduct.id}`, formData);
      toast.success("Product updated successfully");
      setFormModalOpen(false);
      fetchProducts();
    } catch (error) {
      if (error.response && error.response.data.errors) {
        setFormErrors(error.response.data.errors);
      } else {
        toast.error("Failed to update product");
      }
    }
  };

  const handleDeleteProduct = async () => {
    try {
      await api.delete(`v1/admin/products/${currentProduct.id}`);
      toast.success("Product deleted successfully");
      setDeleteModalOpen(false);
      fetchProducts();
    } catch (error) {
      toast.error("Failed to delete product");
    }
  };

  const handleUploadImages = async (formData) => {
    try {
      const response = await api.post(
        `v1/admin/products/${currentProduct.id}/images`,
        formData,
        {
          headers: {
            "Content-Type": "multipart/form-data",
          },
        }
      );
      return response.data.data;
    } catch (error) {
      throw error;
    }
  };

  // Sorting and filtering
  const handleSort = (field) => {
    if (sortField === field) {
      setSortDirection(sortDirection === "asc" ? "desc" : "asc");
    } else {
      setSortField(field);
      setSortDirection("asc");
    }
  };

  const resetFilters = () => {
    setSearchQuery("");
    setFilterCategory("");
    setFilterStatus("");
    setSortField("created_at");
    setSortDirection("desc");
  };

  // Render product status badge
  const renderStatusBadge = (status) => {
    let bgColor, textColor;

    switch (status) {
      case "active":
        bgColor = "bg-green-100";
        textColor = "text-green-800";
        break;
      case "draft":
        bgColor = "bg-gray-100";
        textColor = "text-gray-800";
        break;
      case "out_of_stock":
        bgColor = "bg-red-100";
        textColor = "text-red-800";
        break;
      default:
        bgColor = "bg-blue-100";
        textColor = "text-blue-800";
    }

    return (
      <span
        className={`px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full ${bgColor} ${textColor}`}
      >
        {status === "out_of_stock"
          ? "Out of Stock"
          : status.charAt(0).toUpperCase() + status.slice(1)}
      </span>
    );
  };

  return (
    <div className="p-6">
      <div className="mb-6 flex justify-between items-center">
        <div>
          <h1 className="text-2xl font-bold text-gray-800">
            Products Management
          </h1>
          <p className="text-gray-600">Manage your store's products</p>
        </div>
        <button
          onClick={openCreateForm}
          className="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg flex items-center transition-colors"
        >
          <Plus size={16} className="mr-2" />
          Add New Product
        </button>
      </div>

      {/* Filters & Search */}
      <div className="bg-white rounded-xl shadow-md p-4 mb-6">
        <div className="flex flex-col md:flex-row md:items-center justify-between space-y-4 md:space-y-0 md:space-x-4">
          <div className="flex-1 relative">
            <input
              type="text"
              placeholder="Search products..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="w-full px-4 py-2 pr-10 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
            />
            <Search
              size={18}
              className="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400"
            />
          </div>

          <div className="flex flex-col md:flex-row space-y-2 md:space-y-0 md:space-x-4">
            <div className="w-full md:w-44">
              <select
                value={filterCategory}
                onChange={(e) => setFilterCategory(e.target.value)}
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
              >
                <option value="">All Categories</option>
                {categories.map((category) => (
                  <option key={category.id} value={category.id}>
                    {category.name}
                  </option>
                ))}
              </select>
            </div>

            <div className="w-full md:w-44">
              <select
                value={filterStatus}
                onChange={(e) => setFilterStatus(e.target.value)}
                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
              >
                <option value="">All Statuses</option>
                <option value="active">Active</option>
                <option value="draft">Draft</option>
                <option value="out_of_stock">Out of Stock</option>
              </select>
            </div>

            <button
              onClick={resetFilters}
              className="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-colors flex items-center justify-center"
            >
              <X size={16} className="mr-2" />
              Reset Filters
            </button>
          </div>
        </div>
      </div>

      {/* Products Table */}
      <div className="bg-white rounded-xl shadow-md overflow-hidden">
        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  <button
                    onClick={() => handleSort("id")}
                    className="flex items-center"
                  >
                    ID
                    {sortField === "id" && (
                      <ArrowUpDown size={16} className="ml-1" />
                    )}
                  </button>
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  <button
                    onClick={() => handleSort("name")}
                    className="flex items-center"
                  >
                    Product Name
                    {sortField === "name" && (
                      <ArrowUpDown size={16} className="ml-1" />
                    )}
                  </button>
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  <button
                    onClick={() => handleSort("price")}
                    className="flex items-center"
                  >
                    Price
                    {sortField === "price" && (
                      <ArrowUpDown size={16} className="ml-1" />
                    )}
                  </button>
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Category
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  <button
                    onClick={() => handleSort("stock")}
                    className="flex items-center"
                  >
                    Stock
                    {sortField === "stock" && (
                      <ArrowUpDown size={16} className="ml-1" />
                    )}
                  </button>
                </th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  <button
                    onClick={() => handleSort("status")}
                    className="flex items-center"
                  >
                    Status
                    {sortField === "status" && (
                      <ArrowUpDown size={16} className="ml-1" />
                    )}
                  </button>
                </th>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {loading ? (
                <tr>
                  <td colSpan="7" className="px-6 py-16 text-center">
                    <Loader
                      size={30}
                      className="animate-spin mx-auto text-indigo-600"
                    />
                    <p className="mt-2 text-gray-500">Loading products...</p>
                  </td>
                </tr>
              ) : Array.isArray(products) && products.length === 0 ? (
                <tr>
                  <td colSpan="7" className="px-6 py-16 text-center">
                    <div className="flex flex-col items-center">
                      <ShoppingCart size={40} className="text-gray-300 mb-2" />
                      <h3 className="text-lg font-medium text-gray-900">
                        No products found
                      </h3>
                      <p className="text-gray-500 mt-1 mb-4">
                        {searchQuery || filterCategory || filterStatus
                          ? "Try changing your search or filters"
                          : "Start by adding your first product"}
                      </p>
                      <button
                        onClick={openCreateForm}
                        className="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg flex items-center transition-colors"
                      >
                        <Plus size={16} className="mr-2" />
                        Add New Product
                      </button>
                    </div>
                  </td>
                </tr>
              ) : (
                Array.isArray(products) &&
                products.map((product) => {
                  // console.log("rendering product:", product);
                  return (
                    <tr key={product.id} className="hover:bg-gray-50">
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        #{product.id}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        <div className="flex items-center">
                          <div className="h-10 w-10 flex-shrink-0">
                            {product.main_image ? (
                              <img
                                className="h-10 w-10 rounded-md object-cover"
                                src={product.main_image}
                                alt={product.name}
                              />
                            ) : (
                              <div className="h-10 w-10 rounded-md bg-gray-200 flex items-center justify-center">
                                <ImageIcon
                                  size={16}
                                  className="text-gray-500"
                                />
                              </div>
                            )}
                          </div>
                          <div className="ml-4">
                            <div className="text-sm font-medium text-gray-900 truncate max-w-xs">
                              {product.name}
                            </div>
                            {product.sku && (
                              <div className="text-xs text-gray-500">
                                SKU: {product.sku}
                              </div>
                            )}
                          </div>
                        </div>
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        ${parseFloat(product.price).toFixed(2)}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {product.category?.name || "Uncategorized"}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {product.stock !== null ? product.stock : "N/A"}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap">
                        {renderStatusBadge(product.status)}
                      </td>
                      <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <div className="flex justify-end space-x-2">
                          <button
                            onClick={() => openImagesModal(product)}
                            className="p-1 text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded-full transition-colors"
                            title="Manage Images"
                          >
                            <ImageIcon size={18} />
                          </button>
                          <button
                            onClick={() => openEditForm(product)}
                            className="p-1 text-indigo-600 hover:text-indigo-800 hover:bg-indigo-50 rounded-full transition-colors"
                            title="Edit Product"
                          >
                            <Edit size={18} />
                          </button>
                          <button
                            onClick={() => openDeleteModal(product)}
                            className="p-1 text-red-600 hover:text-red-800 hover:bg-red-50 rounded-full transition-colors"
                            title="Delete Product"
                          >
                            <Trash2 size={18} />
                          </button>
                        </div>
                      </td>
                    </tr>
                  );
                })
              )}
            </tbody>
          </table>
        </div>

        {/* Pagination */}
        {!loading && products.length > 0 && (
          <div className="px-6 py-4 flex items-center justify-between border-t border-gray-200">
            <div className="text-sm text-gray-500">
              Page {currentPage} of {totalPages}
            </div>

            <div className="flex space-x-2">
              <button
                onClick={() => setCurrentPage(Math.max(1, currentPage - 1))}
                disabled={currentPage === 1}
                className="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
              >
                <ChevronLeft size={16} className="mr-1" />
                Previous
              </button>

              <button
                onClick={() =>
                  setCurrentPage(Math.min(totalPages, currentPage + 1))
                }
                disabled={currentPage === totalPages}
                className="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
              >
                Next
                <ChevronRight size={16} className="ml-1" />
              </button>
            </div>
          </div>
        )}
      </div>

      {/* Product Form Modal */}
      <Modal
        isOpen={formModalOpen}
        onClose={() => setFormModalOpen(false)}
        title={formMode === "create" ? "Add New Product" : "Edit Product"}
      >
        <form className="space-y-6">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div className="md:col-span-2">
              <label
                htmlFor="name"
                className="block text-sm font-medium text-gray-700 mb-1"
              >
                Product Name*
              </label>
              <input
                id="name"
                name="name"
                type="text"
                value={formData.name}
                onChange={handleInputChange}
                className={`block w-full px-4 py-2 border ${
                  formErrors.name
                    ? "border-red-300 focus:ring-red-500"
                    : "border-gray-300 focus:ring-indigo-500"
                } rounded-md shadow-sm focus:outline-none focus:border-indigo-500 focus:ring-1 sm:text-sm`}
              />
              {formErrors.name && (
                <p className="mt-1 text-sm text-red-600">{formErrors.name}</p>
              )}
            </div>

            <div className="md:col-span-2">
              <label
                htmlFor="description"
                className="block text-sm font-medium text-gray-700 mb-1"
              >
                Description
              </label>
              <textarea
                id="description"
                name="description"
                rows="3"
                value={formData.description}
                onChange={handleInputChange}
                className="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:border-indigo-500 focus:ring-indigo-500 focus:ring-1 sm:text-sm"
              />
            </div>

            <div>
              <label
                htmlFor="price"
                className="block text-sm font-medium text-gray-700 mb-1"
              >
                Price*
              </label>
              <div className="relative">
                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <span className="text-gray-500 sm:text-sm">$</span>
                </div>
                <input
                  id="price"
                  name="price"
                  type="text"
                  value={formData.price}
                  onChange={handleInputChange}
                  className={`block w-full pl-7 pr-4 py-2 border ${
                    formErrors.price
                      ? "border-red-300 focus:ring-red-500"
                      : "border-gray-300 focus:ring-indigo-500"
                  } rounded-md shadow-sm focus:outline-none focus:border-indigo-500 focus:ring-1 sm:text-sm`}
                  placeholder="0.00"
                />
              </div>
              {formErrors.price && (
                <p className="mt-1 text-sm text-red-600">{formErrors.price}</p>
              )}
            </div>

            <div>
              <label
                htmlFor="category_id"
                className="block text-sm font-medium text-gray-700 mb-1"
              >
                Category*
              </label>
              <select
                id="category_id"
                name="category_id"
                value={formData.category_id}
                onChange={handleInputChange}
                className={`block w-full px-4 py-2 border ${
                  formErrors.category_id
                    ? "border-red-300 focus:ring-red-500"
                    : "border-gray-300 focus:ring-indigo-500"
                } rounded-md shadow-sm focus:outline-none focus:border-indigo-500 focus:ring-1 sm:text-sm`}
              >
                <option value="">Select a category</option>
                {categories.map((category) => (
                  <option key={category.id} value={category.id}>
                    {category.name}
                  </option>
                ))}
              </select>
              {formErrors.category_id && (
                <p className="mt-1 text-sm text-red-600">
                  {formErrors.category_id}
                </p>
              )}
            </div>

            <div>
              <label
                htmlFor="stock"
                className="block text-sm font-medium text-gray-700 mb-1"
              >
                Stock
              </label>
              <input
                id="stock"
                name="stock"
                type="text"
                value={formData.stock}
                onChange={handleInputChange}
                className={`block w-full px-4 py-2 border ${
                  formErrors.stock
                    ? "border-red-300 focus:ring-red-500"
                    : "border-gray-300 focus:ring-indigo-500"
                } rounded-md shadow-sm focus:outline-none focus:border-indigo-500 focus:ring-1 sm:text-sm`}
                placeholder="Leave empty for unlimited"
              />
              {formErrors.stock && (
                <p className="mt-1 text-sm text-red-600">{formErrors.stock}</p>
              )}
            </div>

            <div>
              <label
                htmlFor="sku"
                className="block text-sm font-medium text-gray-700 mb-1"
              >
                SKU
              </label>
              <input
                id="sku"
                name="sku"
                type="text"
                value={formData.sku}
                onChange={handleInputChange}
                className="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:border-indigo-500 focus:ring-indigo-500 focus:ring-1 sm:text-sm"
              />
            </div>

            <div>
              <label
                htmlFor="status"
                className="block text-sm font-medium text-gray-700 mb-1"
              >
                Status
              </label>
              <select
                id="status"
                name="status"
                value={formData.status}
                onChange={handleInputChange}
                className="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:border-indigo-500 focus:ring-indigo-500 focus:ring-1 sm:text-sm"
              >
                <option value="active">Active</option>
                <option value="draft">Draft</option>
                <option value="out_of_stock">Out of Stock</option>
              </select>
            </div>

            <div className="flex items-center pt-4">
              <input
                id="featured"
                name="featured"
                type="checkbox"
                checked={formData.featured}
                onChange={handleInputChange}
                className="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
              />
              <label
                htmlFor="featured"
                className="ml-2 block text-sm text-gray-700"
              >
                Featured Product
              </label>
            </div>
          </div>

          <div className="flex justify-end space-x-3 pt-4">
            <button
              type="button"
              onClick={() => setFormModalOpen(false)}
              className="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              Cancel
            </button>
            <button
              type="button"
              onClick={
                formMode === "create"
                  ? handleCreateProduct
                  : handleUpdateProduct
              }
              className="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              {formMode === "create" ? "Create Product" : "Update Product"}
            </button>
          </div>
        </form>
      </Modal>

      {/* Delete Confirmation Modal */}
      <Modal
        isOpen={deleteModalOpen}
        onClose={() => setDeleteModalOpen(false)}
        title="Delete Product"
      >
        <div className="text-center">
          <div className="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
            <Trash2 size={24} className="text-red-600" />
          </div>
          <h3 className="text-lg leading-6 font-medium text-gray-900 mb-2">
            Are you sure?
          </h3>
          <p className="text-sm text-gray-500 mb-6">
            You are about to delete the product "{currentProduct?.name}". This
            action cannot be undone.
          </p>
          <div className="flex justify-center space-x-3">
            <button
              type="button"
              onClick={() => setDeleteModalOpen(false)}
              className="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              Cancel
            </button>
            <button
              type="button"
              onClick={handleDeleteProduct}
              className="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
            >
              Delete
            </button>
          </div>
        </div>
      </Modal>

      {/* Images Modal */}
      <Modal
        isOpen={imagesModalOpen}
        onClose={() => setImagesModalOpen(false)}
        title="Manage Product Images"
      >
        {currentProduct && (
          <ImageUploader
            onUpload={handleUploadImages}
            initialImages={currentProduct.images || []}
          />
        )}
      </Modal>
    </div>
  );
};

// Adding the missing ShoppingCart component
const ShoppingCart = ({ size, className }) => {
  return (
    <svg
      xmlns="http://www.w3.org/2000/svg"
      width={size}
      height={size}
      className={className}
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth="2"
      strokeLinecap="round"
      strokeLinejoin="round"
    >
      <circle cx="8" cy="21" r="1"></circle>
      <circle cx="19" cy="21" r="1"></circle>
      <path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12"></path>
    </svg>
  );
};

export default Products;