import { ArrowLeft } from "lucide-react";
import { Container } from "../components/ui/Container";
import { Button } from "../components/ui/Button";
import { Reveal } from "../components/ui/Reveal";

export default function NotFound() {
  return (
    <section className="flex min-h-[80vh] items-center py-32">
      <Container className="text-center">
        <Reveal>
          <p className="font-display text-8xl text-gradient-gold sm:text-9xl">404</p>
          <h1 className="mt-4 font-display text-3xl text-white sm:text-4xl">Page Not Found</h1>
          <p className="mx-auto mt-4 max-w-md text-white/55">
            Looks like this page skipped leg day. Let's get you back on track.
          </p>
          <div className="mt-8 flex justify-center">
            <Button to="/" icon={<ArrowLeft size={16} />}>
              Back To Home
            </Button>
          </div>
        </Reveal>
      </Container>
    </section>
  );
}
