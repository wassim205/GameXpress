import React, { useState, useEffect, useContext } from "react";
import { Link, useNavigate } from "react-router-dom";
import { motion, AnimatePresence } from "framer-motion";
import { AuthContext } from "../context/AuthContext";
import api from "../services/apiService";
import { toast } from "./NotificationProvider";
import { 
  Home, 
  ShoppingCart, 
  Tag, 
  Image, 
  Settings, 
  User, 
  LogOut, 
  ChevronDown, 
  Menu, 
  X,
  Activity,
  Users,
  Gift
} from "lucide-react";

// Stats card component
const StatCard = ({ icon, title, value, color }) => (
  <motion.div 
    whileHover={{ scale: 1.03 }}
    className={`bg-white rounded-xl shadow-lg p-6 flex items-center space-x-4 border-l-4 ${color}`}
  >
    <div className={`p-3 rounded-full ${color.replace('border-', 'bg-').replace('-500', '-100')}`}>
      {icon}
    </div>
    <div>
      <p className="text-gray-500 text-sm font-medium">{title}</p>
      <h3 className="text-2xl font-bold">{value}</h3>
    </div>
  </motion.div>
);

const Dashboard = () => {
  const { user, logout } = useContext(AuthContext);
  const navigate = useNavigate();
  const [sidebarOpen, setSidebarOpen] = useState(true);
  const [stats, setStats] = useState({
    products: 0,
    categories: 0,
    users: 0,
    revenue: 0
  });
  const [loading, setLoading] = useState(true);
  const [activeMenu, setActiveMenu] = useState('dashboard');

  useEffect(() => {
    const fetchStats = async () => {
      try {
        const response = await api.get("v1/admin/dashboard", {
          headers: {
            Authorization: `Bearer ${localStorage.getItem("token")}`,
          },
        });
        setStats(response.data);
      } catch (error) {
        if (error.response) {
          toast.error(`Error: ${error.response.data.message || "Failed to load statistics"}`);
        } else {
          toast.error("An unexpected error occurred");
        }
      } finally {
        setLoading(false);
      }
    };

    fetchStats();
  }, []);

  const handleLogout = async () => {
    navigate("/logout");
  };

  const toggleSidebar = () => {
    setSidebarOpen(!sidebarOpen);
  };

  const recentOrders = [
    { id: '#ORD-5298', customer: 'John Doe', date: '2025-04-08', status: 'Completed', amount: '$124.00' },
    { id: '#ORD-5297', customer: 'Emily Parker', date: '2025-04-07', status: 'Processing', amount: '$89.99' },
    { id: '#ORD-5296', customer: 'Michael Scott', date: '2025-04-07', status: 'Completed', amount: '$156.49' },
    { id: '#ORD-5295', customer: 'Sophia Chen', date: '2025-04-06', status: 'Pending', amount: '$232.50' }
  ];

  return (
    <div className="flex h-screen bg-gray-50">
      {/* Sidebar */}
      <AnimatePresence>
        {sidebarOpen && (
          <motion.aside
            initial={{ x: -300 }}
            animate={{ x: 0 }}
            exit={{ x: -300 }}
            transition={{ duration: 0.3 }}
            className="w-64 bg-gradient-to-br from-indigo-800 to-indigo-900 text-white fixed inset-y-0 left-0 z-50 shadow-xl"
          >
            <div className="p-6">
              <div className="flex items-center space-x-3 mb-10">
                <div className="bg-white p-2 rounded-lg">
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    className="h-8 w-8 text-indigo-600"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                  >
                    <path
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      strokeWidth={2}
                      d="M13 10V3L4 14h7v7l9-11h-7z"
                    />
                  </svg>
                </div>
                <div>
                  <h1 className="text-xl font-bold">GameXpress</h1>
                  <p className="text-xs text-indigo-200">Admin Dashboard</p>
                </div>
              </div>

              <nav className="space-y-1">
                <Link to="/dashboard">
                  <motion.div 
                    whileHover={{ x: 4 }}
                    onClick={() => setActiveMenu('dashboard')}
                    className={`flex items-center space-x-3 p-3 rounded-lg transition-colors ${
                      activeMenu === 'dashboard' ? 'bg-indigo-700' : 'hover:bg-indigo-700/50'
                    }`}
                  >
                    <Home size={20} />
                    <span>Dashboard</span>
                  </motion.div>
                </Link>

                <Link to="/products">
                  <motion.div 
                    whileHover={{ x: 4 }}
                    onClick={() => setActiveMenu('products')}
                    className={`flex items-center space-x-3 p-3 rounded-lg transition-colors ${
                      activeMenu === 'products' ? 'bg-indigo-700' : 'hover:bg-indigo-700/50'
                    }`}
                  >
                    <ShoppingCart size={20} />
                    <span>Products</span>
                  </motion.div>
                </Link>

                <Link to="/categories">
                  <motion.div 
                    whileHover={{ x: 4 }}
                    onClick={() => setActiveMenu('categories')}
                    className={`flex items-center space-x-3 p-3 rounded-lg transition-colors ${
                      activeMenu === 'categories' ? 'bg-indigo-700' : 'hover:bg-indigo-700/50'
                    }`}
                  >
                    <Tag size={20} />
                    <span>Categories</span>
                  </motion.div>
                </Link>

                <Link to="/images">
                  <motion.div 
                    whileHover={{ x: 4 }}
                    onClick={() => setActiveMenu('images')}
                    className={`flex items-center space-x-3 p-3 rounded-lg transition-colors ${
                      activeMenu === 'images' ? 'bg-indigo-700' : 'hover:bg-indigo-700/50'
                    }`}
                  >
                    <Image size={20} />
                    <span>Images</span>
                  </motion.div>
                </Link>

                <Link to="/users">
                  <motion.div 
                    whileHover={{ x: 4 }}
                    onClick={() => setActiveMenu('users')}
                    className={`flex items-center space-x-3 p-3 rounded-lg transition-colors ${
                      activeMenu === 'users' ? 'bg-indigo-700' : 'hover:bg-indigo-700/50'
                    }`}
                  >
                    <Users size={20} />
                    <span>Users</span>
                  </motion.div>
                </Link>

                <Link to="/settings">
                  <motion.div 
                    whileHover={{ x: 4 }}
                    onClick={() => setActiveMenu('settings')}
                    className={`flex items-center space-x-3 p-3 rounded-lg transition-colors ${
                      activeMenu === 'settings' ? 'bg-indigo-700' : 'hover:bg-indigo-700/50'
                    }`}
                  >
                    <Settings size={20} />
                    <span>Settings</span>
                  </motion.div>
                </Link>
              </nav>
            </div>

            <div className="absolute bottom-0 w-full p-6">
              <div className="bg-indigo-700/50 rounded-lg p-4">
                <div className="flex items-center space-x-3">
                  <div className="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-800 font-bold">
                    {user?.name?.charAt(0) || "U"}
                  </div>
                  <div className="flex-1 min-w-0">
                    <p className="text-sm font-medium truncate">{user?.name || "User"}</p>
                    <p className="text-xs text-indigo-200">{user?.role || "Admin"}</p>
                  </div>
                </div>
                <button
                  onClick={handleLogout}
                  className="mt-3 w-full flex items-center justify-center space-x-2 p-2 text-sm bg-indigo-600 hover:bg-indigo-500 rounded-lg transition-colors"
                >
                  <LogOut size={16} />
                  <span>Logout</span>
                </button>
              </div>
            </div>
          </motion.aside>
        )}
      </AnimatePresence>

      {/* Main Content */}
      <div className={`flex-1 ${sidebarOpen ? "ml-64" : "ml-0"} transition-all duration-300`}>
        {/* Header */}
        <header className="bg-white shadow-sm">
          <div className="flex justify-between items-center px-6 py-4">
            <button onClick={toggleSidebar} className="p-2 rounded-lg hover:bg-gray-100">
              {sidebarOpen ? <X size={20} /> : <Menu size={20} />}
            </button>
            
            <div className="flex items-center space-x-4">
              <div className="relative">
                <span className="absolute top-0 right-0 h-2 w-2 bg-red-500 rounded-full"></span>
                <button className="p-2 rounded-lg hover:bg-gray-100">
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    className="h-6 w-6 text-gray-500"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                  >
                    <path
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      strokeWidth={2}
                      d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
                    />
                  </svg>
                </button>
              </div>
              
              <div className="hidden md:flex items-center space-x-3">
                <div className="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-800 font-bold">
                  {user?.name?.charAt(0) || "U"}
                </div>
                <div>
                  <p className="text-sm font-medium">{user?.name || "User"}</p>
                  <p className="text-xs text-gray-500">{user?.role || "Admin"}</p>
                </div>
                <ChevronDown size={14} className="text-gray-500" />
              </div>
            </div>
          </div>
        </header>

        {/* Dashboard Content */}
        <motion.main 
          initial={{ opacity: 0 }}
          animate={{ opacity: 1 }}
          className="p-6"
        >
          <div className="mb-8">
            <h1 className="text-2xl font-bold text-gray-800">Welcome back, {user?.name || "Admin"}!</h1>
            <p className="text-gray-600">Here's what's happening with your store today.</p>
          </div>

          {/* Stats Grid */}
          {loading ? (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
              {[1, 2, 3, 4].map((i) => (
                <div key={i} className="h-32 bg-gray-200 rounded-xl animate-pulse"></div>
              ))}
            </div>
          ) : (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
              <StatCard 
                icon={<ShoppingCart className="h-6 w-6 text-indigo-600" />} 
                title="Total Products" 
                value={stats.products} 
                color="border-indigo-500"
              />
              <StatCard 
                icon={<Tag className="h-6 w-6 text-green-600" />} 
                title="Categories" 
                value={stats.categories} 
                color="border-green-500"
              />
              <StatCard 
                icon={<Users className="h-6 w-6 text-blue-600" />} 
                title="Users" 
                value={stats.users} 
                color="border-blue-500"
              />
              <StatCard 
                icon={<Activity className="h-6 w-6 text-purple-600" />} 
                title="Revenue" 
                value={`$${stats.revenue || 0}`} 
                color="border-purple-500"
              />
            </div>
          )}

          {/* Charts & Recent Orders */}
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {/* Recent Orders */}
            <motion.div 
              initial={{ y: 20, opacity: 0 }}
              animate={{ y: 0, opacity: 1 }}
              transition={{ delay: 0.2 }}
              className="bg-white rounded-xl shadow-lg col-span-2"
            >
              <div className="p-6 border-b border-gray-100">
                <h2 className="text-lg font-bold text-gray-800">Recent Orders</h2>
              </div>
              <div className="overflow-x-auto">
                <table className="min-w-full divide-y divide-gray-200">
                  <thead className="bg-gray-50">
                    <tr>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Order ID
                      </th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Customer
                      </th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Date
                      </th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Status
                      </th>
                      <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Amount
                      </th>
                    </tr>
                  </thead>
                  <tbody className="bg-white divide-y divide-gray-200">
                    {recentOrders.map((order, index) => (
                      <tr key={index}>
                        <td className="px-6 py-4 whitespace-nowrap">
                          <div className="text-sm font-medium text-indigo-600">{order.id}</div>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap">
                          <div className="text-sm text-gray-900">{order.customer}</div>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap">
                          <div className="text-sm text-gray-500">{order.date}</div>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap">
                          <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                            ${order.status === 'Completed' ? 'bg-green-100 text-green-800' : 
                              order.status === 'Processing' ? 'bg-blue-100 text-blue-800' : 
                              'bg-yellow-100 text-yellow-800'}`}>
                            {order.status}
                          </span>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                          {order.amount}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
              <div className="p-4 border-t border-gray-100 text-center">
                <Link to="/orders" className="text-sm text-indigo-600 hover:text-indigo-700 font-medium">
                  View all orders
                </Link>
              </div>
            </motion.div>

            {/* Quick Actions */}
            <motion.div 
              initial={{ y: 20, opacity: 0 }}
              animate={{ y: 0, opacity: 1 }}
              transition={{ delay: 0.3 }}
              className="bg-white rounded-xl shadow-lg"
            >
              <div className="p-6 border-b border-gray-100">
                <h2 className="text-lg font-bold text-gray-800">Quick Actions</h2>
              </div>
              <div className="p-6 space-y-4">
                <Link to="/products/new">
                  <motion.div 
                    whileHover={{ scale: 1.02 }}
                    className="flex items-center p-4 border border-gray-100 rounded-lg hover:bg-gray-50 transition-colors"
                  >
                    <div className="p-2 bg-indigo-100 rounded-lg">
                      <ShoppingCart className="h-5 w-5 text-indigo-600" />
                    </div>
                    <div className="ml-4">
                      <p className="text-sm font-medium text-gray-900">Add New Product</p>
                      <p className="text-xs text-gray-500">Create a new product listing</p>
                    </div>
                  </motion.div>
                </Link>

                <Link to="/categories/new">
                  <motion.div 
                    whileHover={{ scale: 1.02 }}
                    className="flex items-center p-4 border border-gray-100 rounded-lg hover:bg-gray-50 transition-colors"
                  >
                    <div className="p-2 bg-green-100 rounded-lg">
                      <Tag className="h-5 w-5 text-green-600" />
                    </div>
                    <div className="ml-4">
                      <p className="text-sm font-medium text-gray-900">Create Category</p>
                      <p className="text-xs text-gray-500">Add a new product category</p>
                    </div>
                  </motion.div>
                </Link>

                <Link to="/special-offers">
                  <motion.div 
                    whileHover={{ scale: 1.02 }}
                    className="flex items-center p-4 border border-gray-100 rounded-lg hover:bg-gray-50 transition-colors"
                  >
                    <div className="p-2 bg-purple-100 rounded-lg">
                      <Gift className="h-5 w-5 text-purple-600" />
                    </div>
                    <div className="ml-4">
                      <p className="text-sm font-medium text-gray-900">Manage Promotions</p>
                      <p className="text-xs text-gray-500">Create and edit special offers</p>
                    </div>
                  </motion.div>
                </Link>

                <Link to="/users">
                  <motion.div 
                    whileHover={{ scale: 1.02 }}
                    className="flex items-center p-4 border border-gray-100 rounded-lg hover:bg-gray-50 transition-colors"
                  >
                    <div className="p-2 bg-blue-100 rounded-lg">
                      <User className="h-5 w-5 text-blue-600" />
                    </div>
                    <div className="ml-4">
                      <p className="text-sm font-medium text-gray-900">Manage Users</p>
                      <p className="text-xs text-gray-500">Review and edit user accounts</p>
                    </div>
                  </motion.div>
                </Link>
              </div>
            </motion.div>
          </div>
        </motion.main>
      </div>
    </div>
  );
};

export default Dashboard;