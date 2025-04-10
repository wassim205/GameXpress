import { RouterProvider } from "react-router-dom";
import { router } from "./Routes/Router";
import { AuthProvider } from "./context/AuthContext";
import { NotificationProvider } from "./components/NotificationProvider";

import "react-toastify/dist/ReactToastify.css";
import "./styles/toastStyles.css";

const App = () => {
  return (
    <NotificationProvider>
      <AuthProvider>
        <RouterProvider router={router} />
      </AuthProvider>
    </NotificationProvider>
  );
};

export default App;
