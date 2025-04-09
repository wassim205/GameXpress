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
import Logout from "../components/Logout";
import ProtectedRoute from "./ProtectedRoute";

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
]);


