import React from "react";
import { Link } from "react-router-dom";
import { motion } from "framer-motion";
import {
  Home,
  ShoppingCart,
  Tag,
  Image,
  Settings,
  Users,
  LogOut,
} from "lucide-react";

const Sidebar = ({ activeMenu, setActiveMenu, handleLogout }) => {
  return (
    <aside className="w-64 bg-gradient-to-br from-indigo-800 to-indigo-900 text-white fixed inset-y-0 left-0 z-50 shadow-xl">
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
              onClick={() => setActiveMenu("dashboard")}
              className={`flex items-center space-x-3 p-3 rounded-lg transition-colors ${
                activeMenu === "dashboard"
                  ? "bg-indigo-700"
                  : "hover:bg-indigo-700/50"
              }`}
            >
              <Home size={20} />
              <span>Dashboard</span>
            </motion.div>
          </Link>

          <Link to="/products">
            <motion.div
              whileHover={{ x: 4 }}
              onClick={() => setActiveMenu("products")}
              className={`flex items-center space-x-3 p-3 rounded-lg transition-colors ${
                activeMenu === "products"
                  ? "bg-indigo-700"
                  : "hover:bg-indigo-700/50"
              }`}
            >
              <ShoppingCart size={20} />
              <span>Products</span>
            </motion.div>
          </Link>

          <Link to="/categories">
            <motion.div
              whileHover={{ x: 4 }}
              onClick={() => setActiveMenu("categories")}
              className={`flex items-center space-x-3 p-3 rounded-lg transition-colors ${
                activeMenu === "categories"
                  ? "bg-indigo-700"
                  : "hover:bg-indigo-700/50"
              }`}
            >
              <Tag size={20} />
              <span>Categories</span>
            </motion.div>
          </Link>

          <Link to="/images">
            <motion.div
              whileHover={{ x: 4 }}
              onClick={() => setActiveMenu("images")}
              className={`flex items-center space-x-3 p-3 rounded-lg transition-colors ${
                activeMenu === "images"
                  ? "bg-indigo-700"
                  : "hover:bg-indigo-700/50"
              }`}
            >
              <Image size={20} />
              <span>Images</span>
            </motion.div>
          </Link>

          <Link to="/users">
            <motion.div
              whileHover={{ x: 4 }}
              onClick={() => setActiveMenu("users")}
              className={`flex items-center space-x-3 p-3 rounded-lg transition-colors ${
                activeMenu === "users"
                  ? "bg-indigo-700"
                  : "hover:bg-indigo-700/50"
              }`}
            >
              <Users size={20} />
              <span>Users</span>
            </motion.div>
          </Link>

          <Link to="/settings">
            <motion.div
              whileHover={{ x: 4 }}
              onClick={() => setActiveMenu("settings")}
              className={`flex items-center space-x-3 p-3 rounded-lg transition-colors ${
                activeMenu === "settings"
                  ? "bg-indigo-700"
                  : "hover:bg-indigo-700/50"
              }`}
            >
              <Settings size={20} />
              <span>Settings</span>
            </motion.div>
          </Link>

          <button
            onClick={handleLogout}
            className="w-full flex items-center space-x-3 p-3 rounded-lg hover:bg-indigo-700/50 transition-colors"
          >
            <LogOut size={20} />
            <span>Logout</span>
          </button>
        </nav>
      </div>
    </aside>
  );
};

export default Sidebar;