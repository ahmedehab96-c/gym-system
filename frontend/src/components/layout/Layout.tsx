import { Outlet } from "react-router-dom";
import { Navbar } from "./Navbar";
import { Footer } from "./Footer";
import { ScrollToTopButton } from "./ScrollToTopButton";
import { ScrollToTopOnRoute } from "./ScrollToTopOnRoute";

export function Layout() {
  return (
    <div className="min-h-screen bg-void">
      <ScrollToTopOnRoute />
      <Navbar />
      <main>
        <Outlet />
      </main>
      <Footer />
      <ScrollToTopButton />
    </div>
  );
}
