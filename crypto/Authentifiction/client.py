#!/usr/bin/env python3

from getpass import getpass
import json
import socket
import os
import sys

# --- Cryptography

from Cryptodome.Protocol import KDF
from Cryptodome.Cipher import AES
import os


def zero_pad(data: bytes):
    pad_length = -len(data) % 16
    return data + b"\x00" * pad_length, pad_length


class CipherCTS:
    def __init__(self, key: bytes):
        assert len(key) == 32
        self.key = key

    def encrypt(self, data: bytes):
        assert len(data) > 16
        iv = os.urandom(16)
        cipher = AES.new(self.key, mode=AES.MODE_CBC, iv=iv)
        padded_data, pad_length = zero_pad(data)
        ciphertext = cipher.encrypt(padded_data)
        last_block = ciphertext[-16:]
        penultimate_block = ciphertext[-32:-16]
        ciphertext = (
            ciphertext[:-32] + last_block + penultimate_block[: 16 - pad_length]
        )
        return iv + ciphertext

    def decrypt(self, data: bytes):
        assert len(data) > 32
        iv, data = data[:16], data[16:]
        cipher = AES.new(self.key, mode=AES.MODE_ECB)
        l = 16 - (-len(data) % 16)
        penultimate_block = data[-(l + 16) : -l]
        tmp = cipher.decrypt(penultimate_block)
        last_block = data[-l:] + tmp[l:]
        ciphertext = data[: -(l + 16)] + last_block + penultimate_block
        cipher = AES.new(self.key, mode=AES.MODE_CBC, iv=iv)
        plaintext = cipher.decrypt(ciphertext)
        return plaintext[: len(data)]


# --- Client ---

CHALLENGE_LENGTH = 16


def derive_key(password: str, salt: str):
    return KDF.scrypt(password, salt, 32, N=2**14, r=8, p=1)


class ClientAuth:
    def __init__(self, user_name: str, password: str):
        self.client_challenge = os.urandom(CHALLENGE_LENGTH)
        self.user_name = user_name
        self.password = password

    def auth_init_request(self):
        return {
            "option": "auth_init",
            "user_name": self.user_name,
            "client_challenge": self.client_challenge.hex(),
        }

    def auth_proof_request(
        self, salt: str, server_proof: bytes, server_challenge: bytes
    ):
        key = derive_key(self.password, salt)
        cipher = CipherCTS(key)
        proof_decrypted = cipher.decrypt(server_proof)

        if proof_decrypted[:CHALLENGE_LENGTH] != self.client_challenge:
            return {
                "error": "server proof has an incorrect client challenge or wrong password"
            }

        if proof_decrypted[-6:] != b"server":
            return {"error": "server proof does not terminate with 'server'"}

        # During the reverse ingeneerie of the protocol, it appears that
        # the user name is not verified by the server. Only the last 6 bytes
        # are checked (after decryption) to correspond to "client".
        client_proof = cipher.encrypt(
            server_challenge + self.user_name.encode() + b"client"
        )

        return {"option": "auth_proof", "client_proof": client_proof.hex()}


def connect(host, port):
    s = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    s.connect((host, port))

    # Phase 1: auth init request
    user_name = input("User name: ")
    password = getpass()
    auth = ClientAuth(user_name, password)
    request = auth.auth_init_request()
    s.sendall(json.dumps(request).encode() + b"\n")
    data = s.recv(1024)
    response = json.loads(data.decode())
    if "error" in response:
        print(f"Error: {response['error']}")
        s.close()
        return

    # Phase 2: prepare auth proof request
    salt = response["salt"]
    server_challenge = bytes.fromhex(response["server_challenge"])
    server_proof = bytes.fromhex(response["server_proof"])

    request = auth.auth_proof_request(salt, server_proof, server_challenge)
    if "error" in request:
        print(f"Error: {request['error']}")
        s.close()
        return

    s.sendall(json.dumps(request).encode() + b"\n")
    data = s.recv(1024)
    response = json.loads(data.decode())
    if "error" in response:
        print(f"Error: {request['error']}")
        s.close()
        return
    print(response["message"])

    # If user is authenticated
    if not response["authenticated"]:
        s.close()
        return

    # Finally: ask to get user file
    choice = input("Get file [y/N]? ").strip()
    if choice != "y" and choice != "Y":
        s.close()
        return

    request = {"option": "get_file"}
    s.sendall(json.dumps(request).encode() + b"\n")
    file_content = b""
    while True:
        data = s.recv(1024)
        file_content += data
        if len(data) < 1024:
            break

    s.close()
    print(file_content.decode())


if __name__ == "__main__":
    argc = len(sys.argv) - 1
    if argc != 2:
        print("Usage: python3 client.py HOST PORT")
        sys.exit()

    connect(sys.argv[1], int(sys.argv[2]))
