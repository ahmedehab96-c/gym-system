import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:image_picker/image_picker.dart';

import '../../../../core/widgets/app_avatar.dart';
import '../../../../core/widgets/async_value_view.dart';
import '../../../../shared/models/trainer.dart';
import '../providers/trainer_profile_provider.dart';

class EditTrainerProfileScreen extends ConsumerStatefulWidget {
  const EditTrainerProfileScreen({super.key});

  @override
  ConsumerState<EditTrainerProfileScreen> createState() => _EditTrainerProfileScreenState();
}

class _EditTrainerProfileScreenState extends ConsumerState<EditTrainerProfileScreen> {
  final _nameController = TextEditingController();
  final _specialtyController = TextEditingController();
  final _experienceController = TextEditingController();
  final _phoneController = TextEditingController();
  final _bioController = TextEditingController();
  bool _initialized = false;

  @override
  void dispose() {
    _nameController.dispose();
    _specialtyController.dispose();
    _experienceController.dispose();
    _phoneController.dispose();
    _bioController.dispose();
    super.dispose();
  }

  void _fillFrom(Trainer trainer) {
    if (_initialized) return;
    _nameController.text = trainer.name;
    _specialtyController.text = trainer.specialty ?? '';
    _experienceController.text = trainer.experience ?? '';
    _phoneController.text = trainer.phone ?? '';
    _bioController.text = trainer.bio ?? '';
    _initialized = true;
  }

  Future<void> _pickPhoto() async {
    final picked = await ImagePicker().pickImage(source: ImageSource.gallery, imageQuality: 80);
    if (picked == null) return;

    final error = await ref.read(trainerProfileControllerProvider.notifier).uploadPhoto(picked.path);
    if (!mounted) return;
    if (error != null) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(error)));
    }
  }

  Future<void> _save() async {
    final error = await ref.read(trainerProfileControllerProvider.notifier).update(
          name: _nameController.text.trim(),
          specialty: _specialtyController.text.trim(),
          experience: _experienceController.text.trim(),
          phone: _phoneController.text.trim(),
          bio: _bioController.text.trim(),
        );

    if (!mounted) return;
    if (error != null) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(error)));
    } else {
      ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Profile updated')));
      Navigator.of(context).pop();
    }
  }

  @override
  Widget build(BuildContext context) {
    final profile = ref.watch(currentTrainerProfileProvider);
    final saving = ref.watch(trainerProfileControllerProvider).isLoading;

    return Scaffold(
      appBar: AppBar(title: const Text('Edit Profile')),
      body: AsyncValueView<Trainer>(
        value: profile,
        onRetry: () => ref.invalidate(currentTrainerProfileProvider),
        data: (trainer) {
          _fillFrom(trainer);

          return ListView(
            padding: const EdgeInsets.all(20),
            children: [
              Center(
                child: Stack(
                  children: [
                    AppAvatar(imageUrl: trainer.photo, name: trainer.name, radius: 44),
                    Positioned(
                      bottom: 0,
                      right: 0,
                      child: GestureDetector(
                        onTap: _pickPhoto,
                        child: CircleAvatar(
                          radius: 15,
                          backgroundColor: Theme.of(context).colorScheme.primary,
                          child: const Icon(Icons.camera_alt_outlined, size: 15, color: Colors.white),
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 28),
              TextField(controller: _nameController, decoration: const InputDecoration(labelText: 'Full Name')),
              const SizedBox(height: 14),
              TextField(controller: _specialtyController, decoration: const InputDecoration(labelText: 'Specialty')),
              const SizedBox(height: 14),
              TextField(controller: _experienceController, decoration: const InputDecoration(labelText: 'Experience')),
              const SizedBox(height: 14),
              TextField(controller: _phoneController, decoration: const InputDecoration(labelText: 'Phone'), keyboardType: TextInputType.phone),
              const SizedBox(height: 14),
              TextField(controller: _bioController, decoration: const InputDecoration(labelText: 'Bio'), maxLines: 4),
              const SizedBox(height: 28),
              ElevatedButton(
                onPressed: saving ? null : _save,
                child: saving
                    ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2.4, color: Colors.white))
                    : const Text('Save Changes'),
              ),
            ],
          );
        },
      ),
    );
  }
}
