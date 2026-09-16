import { Link } from "react-router-dom";
import { Button } from "../components/ui/Button";

export default function AdminNotFound() {
  return (
    <div className="flex flex-col items-center justify-center gap-4 py-24 text-center">
      <p className="text-6xl font-bold text-a-accent">404</p>
      <p className="text-a-muted dark:text-a-dark-muted">This admin page doesn't exist.</p>
      <Link to="/admin/dashboard">
        <Button>Back to Dashboard</Button>
      </Link>
    </div>
  );
}
