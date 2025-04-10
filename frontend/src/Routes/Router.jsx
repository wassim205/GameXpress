import {
  createBrowserRouter,
  RouterProvider,
} from "react-router";

import React from "react";
import App from "../App";
import WelcomePage from "../components/WelcomePage";
import RegisterPage from "../components/RegisterPage";
import LoginPage from "../components/LoginPage";
import Dashboard from "../components/Dashboard";
// import Products from "../components/Products";
import Logout from "../components/Logout";
import ProtectedRoute from "./ProtectedRoute";
import Products from "../components/products";

export const router = createBrowserRouter([
  {
    path: "/",
    element: <WelcomePage />,
    // element: <App />,
  },
  {
    path: "/register",
    element: <RegisterPage/>,
  },
  {
    path: "/login",
    element: <LoginPage/>,
  },
  {
    path: "/logout",
    element: <Logout/>,
  },
  {
    path: "/dashboard",
    element: (
      <ProtectedRoute>
        <Dashboard />
      </ProtectedRoute>
    ),
  },
  {
    path: "/products",
    element: (
      <ProtectedRoute>
      <Products />
      </ProtectedRoute>
    ),
  },
]);


